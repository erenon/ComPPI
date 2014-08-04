<?php

namespace Comppi\BuildBundle\Service\ConfidenceScore\Calculator;

use Doctrine\DBAL\Types\IntegerType as IntegerParameter;

class ComppiStandard implements CalculatorInterface
{
    /*0 - unknown, 1 - experimental, 2 - predicted*/
    private $weights=array('0'=>0.5,'1'=>0.7,'2'=>0.5);


    /**
     * @var Comppi\BuildBundle\Service\LocalizationTranslator\LocalizationTranslator
     */
    private $localizationTranslator;
    private $id;
    
    public function __construct($id) {
        $this->id = $id;
    }

    public function setLocalizationTranslator($translator) {
        $this->localizationTranslator = $translator;
    }

    public function calculate(\Doctrine\DBAL\Connection $connection)
    {

        $protOffset = 0;
        $interactionOffset = 0;
        $blockSize = 500;

    
	$this->conn=$connection; //needed for CalculateLinkConfidence;
        $locQuery=$connection->prepare('SELECT proteinId,localizationId,specieId,confidenceType FROM ProtLocToSystemType LEFT JOIN ProteinToLocalization on ProtLocToSystemType.protLocId=ProteinToLocalization.id LEFT JOIN Protein on Protein.id=ProteinToLocalization.proteinId LEFT JOIN SystemType on SystemType.id=ProtLocToSystemType.systemTypeId WHERE proteinId=?');
        echo (" CompPPI Standard calculator init...\n");
        $this->initCalculation($connection);
        //for each protein
        $ProteinScores=array();
        echo (" Adding proteins from database...\n");
        $protSelect=$connection->prepare("SELECT DISTINCT id FROM Protein ORDER BY id ASC LIMIT ?,?");
        $num=0;

        $protinsert = $connection->prepare(
            'INSERT INTO LocalizationScore(proteinId, majorLocName, calculatorId, score)' .
            ' VALUES(?, ?, ?, ?)'
        );

        $protinsert->bindValue(3, $this->id, IntegerParameter::INTEGER);
        
        $protSelect->bindValue(1, $protOffset, IntegerParameter::INTEGER);
	$protSelect->bindValue(2, $blockSize, IntegerParameter::INTEGER);

	$protSelect->execute();
	
	while ($protSelect->rowCount() > 0) 
	{
	$protIDs=$protSelect->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

	$connection->beginTransaction();
	        

        //calculate protein scores
        foreach($protIDs as $protein)
        {
         //echo($protein['id']."\n");
         $num++;
         $locQuery->bindValue(1,$protein['id'], IntegerParameter::INTEGER);
         $locQuery->execute();
         $localizations=$locQuery->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
         $foundloc=false;
         $entries=array();
	 foreach ($localizations as $row)
	 {
	  //echo($row['localizationId']." ".$row['confidenceType']."\n");
	  $loc=$this->localizationTranslator->getLargelocById($row['localizationId']);
	  //echo($loc."\n");
	  if (!isset($entries[$loc][$row['confidenceType']])) $entries[$loc][$row['confidenceType']]=0;
	  $entries[$loc][$row['confidenceType']]+=1;
	  $foundloc=true;
	 }
	 if($foundloc==false) //no localization information, empty set received
	 {echo("Warning: No localization information for protein #".$protein['id']."!\n"); continue;}

	 foreach($entries as $ProteinLocalization => $pla)
	 {
	  //echo($ProteinLocalization.":");
	  $score=1;
	  foreach($entries[$ProteinLocalization] as $entryClass => $entryNum)
	  {
	      $score*=pow( 1 - $this->weights[$entryClass] , $entryNum );
	      //echo($score."...");
	  }
	  //echo(1- $score."\n");
	  $ProteinScores[$protein['id']][$ProteinLocalization]= 1 - $score;
	  
	  $protinsert->bindValue(1, $protein['id'], IntegerParameter::INTEGER);
	  $protinsert->bindValue(2, $ProteinLocalization);
	  $protinsert->bindValue(4, 1-$score, IntegerParameter::INTEGER);
	  $protinsert->execute();

	 }
         if($num%10000==0) echo($num." proteins added.\n");
        }
	  $connection->commit();

	  // advance cursor
	  $protOffset += $blockSize;

	  $protSelect->closeCursor();
	  $protSelect->bindValue(1, $protOffset, IntegerParameter::INTEGER);
	  $protSelect->execute();
	}
        echo("Calculating link confidence scores...\n");

        $insert = $connection->prepare(
            'INSERT INTO ConfidenceScore(interactionId, calculatorId, score)' .
            ' VALUES(?, ?, ?)'
        );

        $insert->bindValue(2, $this->id, IntegerParameter::INTEGER);

        //no cross-species links
        $interactionSelect = $connection->prepare(
            'SELECT Interaction.id as id FROM Interaction ORDER BY id ASC LIMIT ?, ?'
        );


        $interactionSelect->bindValue(1, $interactionOffset, IntegerParameter::INTEGER);
        $interactionSelect->bindValue(2, $blockSize, IntegerParameter::INTEGER);

        $interactionSelect->execute();

        echo("Inserting confidence score rows...\n");

        while ($interactionSelect->rowCount() > 0) 
        {
	  $interactions = $interactionSelect->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

	  $connection->beginTransaction();

	  foreach ($interactions as $interaction)
	  {
	    $interactionQuery=$connection->prepare('SELECT actorAId,actorBId FROM Interaction WHERE Interaction.id=?');
	    $interactionQuery->bindValue(1,$interaction['id'],IntegerParameter::INTEGER);
	    $interactionQuery->execute();
	    $interactionResult=$interactionQuery->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
	    if((!isset($ProteinScores[$interactionResult[0]['actorAId']])) or (!isset($ProteinScores[$interactionResult[0]['actorBId']])))
	    {echo("Protein for interaction ".$interaction['id']." not found in database (or no localization info), skipping.\n");continue;}

	    //echo($interactionResult[0]['actorAId']."<->".$interactionResult[0]['actorBId']."\n");
	    
	    $value=1;
	    foreach ($this->largeLocs as $localization)
	    {
		if((!isset($ProteinScores[$interactionResult[0]['actorAId']][$localization]))||(!isset($ProteinScores[$interactionResult[0]['actorBId']][$localization]))) 
		  $contribution=0;
		else
		  $contribution=$ProteinScores[$interactionResult[0]['actorAId']][$localization]*$ProteinScores[$interactionResult[0]['actorBId']][$localization];
		$value*=(1-$contribution);
		//echo ($localization."..".$value."...");
	    }
	    $LocalizationConfidence=1-$value;
	    //echo($LocalizationConfidence."\n");
	    //not yet implemented
	    $InteractionConfidence=1;

	    $score= $LocalizationConfidence*$InteractionConfidence;

	      $insert->bindValue(1, $interaction['id']);
	      $insert->bindValue(3, $score);
	      $insert->execute();
	  }

	  $connection->commit();

	  // advance cursor
	  $interactionOffset += $blockSize;

	  $interactionSelect->closeCursor();
	  $interactionSelect->bindValue(1, $interactionOffset, IntegerParameter::INTEGER);
	  $interactionSelect->execute();
        }

        echo("Confidence calculation complete.\n");
    }

    public function getName() {
        return "ComPPI Standard";
    }

    private function initCalculation(\Doctrine\DBAL\Connection $connection) {
        // @TODO this hack is required here because of a PDO bug
        // https://bugs.php.net/bug.php?id=44639
        $connection->getWrappedConnection()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        
        $connection->getConfiguration()->setSQLLogger(null);
	//$connection->getWrappedConnection()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
	
	  $this->largeLocs=array_keys($this->localizationTranslator->getLargelocs());
        }
}

