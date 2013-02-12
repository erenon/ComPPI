<?php

namespace Comppi\BuildBundle\Service\ConfidenceScore\Calculator;

use Doctrine\DBAL\Types\IntegerType as IntegerParameter;

class ComppiStandard implements CalculatorInterface
{
    private $id;
    private $dataSet=array('1'=>0,'2'=>1,'3'=>1,'4'=>0);
    private $useLearning=TRUE;
    private $SampleSize=4;
    private $numberOfIterations=2;
    private $predictionPower=0.5;
    private $weights=array('0'=>0.7,'1'=>0.99,'2'=>0.7);
    private $threshold=0.95;
    private $learningRate=0.01;
    private $randomSeed=42;
    /**
     * @var Comppi\BuildBundle\Service\LocalizationTranslator\LocalizationTranslator
     */
    private $localizationTranslator;
    private $conn;
    /**
     * @var array
     */

    public function __construct($id) {
        $this->id = $id;
    }

    public function setLocalizationTranslator($translator) {
        $this->localizationTranslator = $translator;
    }

    public function calculate(\Doctrine\DBAL\Connection $connection)
    {
        $this->conn=$connection; //needed for CalculateLinkConfidence;
        $locQuery=$connection->prepare('SELECT proteinId,localizationId,specieId,confidenceType FROM ProtLocToSystemType LEFT JOIN ProteinToLocalization on ProtLocToSystemType.protLocId=ProteinToLocalization.id LEFT JOIN Protein on Protein.id=ProteinToLocalization.proteinId LEFT JOIN SystemType on SystemType.id=ProtLocToSystemType.systemTypeId WHERE proteinId=?');
        echo (" CompPPI Standard calculator init...\n");
        $this->initCalculation($connection);
        //for each protein
        ProteinScoreCalculator::$fullProteinList=array();
        echo (" Adding proteins from database...\n");
        $protRes=$connection->query("SELECT DISTINCT id FROM Protein");
        $protIDs=$protRes->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        $num=0;
        foreach($protIDs as $protein)
        {
         $num++;
         $locQuery->bindValue(1,$protein['id'], IntegerParameter::INTEGER);
         $locQuery->execute();
         $localizations=$locQuery->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
         $proteinCalc=new ProteinScoreCalculator();
         if(!$proteinCalc->addEntries($localizations))
            {echo("Warning: No localization information for protein #".$protein['id']."!\n"); continue;}
         ProteinScoreCalculator::$fullProteinList[$protein['id']]=$proteinCalc;
         if($num%10000==0) echo($num."/".count($protIDs)." proteins added.\n");
        }
        echo("Adding protein connection data...\n");
        foreach(ProteinScoreCalculator::$fullProteinList as $protein)
         $protein->addNeighbors($connection);

        ProteinScoreCalculator::$classWeights=$this->weights;

        if($this->useLearning)
        {
          srand($this->randomSeed);
          echo("Executing PLA for weights ({$this->numberOfIterations} iterations)...\n");
          $learningParams=array();
          $learningParams[]=$this->weights[0];
          $learningParams[]=$this->weights[1];
          $learningParams[]=$this->weights[2];
          $learningParams[]=$this->predictionPower;
          $bestParams=$learningParams;
          $bestResult=0;
         for ($iterationNumber=0;$iterationNumber<$this->numberOfIterations;$iterationNumber++)
         {
          ProteinScoreCalculator::$classWeights[0]=$learningParams[0];
          ProteinScoreCalculator::$classWeights[1]=$learningParams[1];
          ProteinScoreCalculator::$classWeights[2]=$learningParams[2];
          $this->predictionPower=$learningParams[3];

          //new iteration: update the average
          foreach(ProteinScoreCalculator::$fullProteinList as $protein)
           $protein->calculateMyLocationScores();

          foreach(ProteinScoreCalculator::$fullProteinList as $protein)
           $protein->calculateNeighborLocationScores();

          ProteinScoreCalculator::RecalcAverage();

          $currentResults=array();
          for ($i=0;$i<$this->SampleSize;$i++)
          {
              ProteinScoreCalculator::$classWeights[0]=$learningParams[0];
              ProteinScoreCalculator::$classWeights[1]=$learningParams[1];
              ProteinScoreCalculator::$classWeights[2]=$learningParams[2];
              $this->predictionPower=$learningParams[3];

              $which=array_rand($this->dataSet);
              $interactionQuery=$connection->prepare("SELECT actorAId,actorBId,specieId FROM Interaction LEFT JOIN Protein on Interaction.actorAId=Protein.id WHERE Interaction.id=?");
              $interactionQuery->bindValue(1,$which,IntegerParameter::INTEGER);
              $interactionQuery->execute();
              $sampleRow=$interactionQuery->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
              //we use the new weights for protein score calculation in-loop, we just don't update the average
              if((!isset(ProteinScoreCalculator::$fullProteinList[$sampleRow[0]['actorAId']])) or (!isset(ProteinScoreCalculator::$fullProteinList[$sampleRow[0]['actorAId']])))
                {echo("Warning: Interaction ".$which." in training set is missing proteins or localizations from the database!\n"); continue;}
              ProteinScoreCalculator::$fullProteinList[$sampleRow[0]['actorAId']]->calculateMyLocationScores();
              ProteinScoreCalculator::$fullProteinList[$sampleRow[0]['actorAId']]->calculateNeighborLocationScores();
              ProteinScoreCalculator::$fullProteinList[$sampleRow[0]['actorBId']]->calculateMyLocationScores();
              ProteinScoreCalculator::$fullProteinList[$sampleRow[0]['actorBId']]->calculateNeighborLocationScores();
              $result=CalculateLinkConfidence($which['link']);
              //classify based on threshold
              if($result>$this->threshold)
                $result=1;
              else $result=0;
              //check hit or miss
              if($result==$this->dataSet[$which])
                $currentResults[]=1;
              else $currentResults[]=-1;
              //weight update
              foreach($learningParams as &$parameter)
               $parameter+=$this->alpha*$parameter*$currentResults[count($currentResults)-1];
              unset($parameter);
          }
          $total=array_sum($currentResults);
          if($total>$bestResult)
            {
             $bestParams=$learningParams;
             $bestResult=$total;
            }

          if($iterationNumber%100==0) echo($iterationNumber."/".$this->numberOfIterations." iterations complete...\n");
         }
         ProteinScoreCalculator::$classWeights[0]=$bestParams[0];
         ProteinScoreCalculator::$classWeights[1]=$bestParams[1];
         ProteinScoreCalculator::$classWeights[2]=$bestParams[2];
         $this->predictionPower=$bestParams[3];
        }

        echo("Calculating final confidence score...\n");
        foreach(ProteinScoreCalculator::$fullProteinList as $protein)
         $protein->calculateMyLocationScores();
        foreach(ProteinScoreCalculator::$fullProteinList as $protein)
         $protein->calculateNeighborLocationScores();

        ProteinScoreCalculator::RecalcAverage();

        $insert = $connection->prepare(
            'INSERT INTO ConfidenceScore(interactionId, calculatorId, score)' .
            ' VALUES(?, ?, ?)'
        );

        $insert->bindValue(2, $this->id, IntegerParameter::INTEGER);

        //no cross-species links
        $interactionSelect = $connection->prepare(
            'SELECT Interaction.id as id FROM Interaction ORDER BY id ASC LIMIT ?, ?'
        );

        $interactionOffset = 0;
        $blockSize = 500;

        $interactionSelect->bindValue(1, $interactionOffset, IntegerParameter::INTEGER);
        $interactionSelect->bindValue(2, $blockSize, IntegerParameter::INTEGER);

        $interactionSelect->execute();

        echo("Inserting confidence score rows...\n");

        while ($interactionSelect->rowCount() > 0) {
            $interactions = $interactionSelect->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

            $connection->beginTransaction();

            foreach ($interactions as $interaction) {


                $score = $this->CalculateLinkConfidence($interaction['id']);

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

        ProteinScoreCalculator::$localizationTranslator=$this->localizationTranslator;
        ProteinScoreCalculator::$largeLocs=$this->largeLocs=array_keys($this->localizationTranslator->getLargelocs());
        }

    private function ApplyNeigborCorrection($myLocScore, $neighborLocScore, $averageNeighborLocScore, $predictionPower) {

        if ($neighborLocScore < $averageNeighborLocScore) {
            return $myLocScore * (1 - $predictionPower + ($predictionPower * $neighborLocScore / $averageNeighborLocScore));
        } else {
            return 1- (1-$myLocScore)*(1- ($predictionPower * ($neighborLocScore - $averageNeighborLocScore) / (1 - $averageNeighborLocScore)) );
        }
    }

    private function CalculateLinkConfidence($linkID)
    {
     $interactionQuery=$this->conn->prepare('SELECT actorAId,actorBId,specieId FROM Interaction LEFT JOIN Protein on Interaction.actorAId=Protein.id WHERE Interaction.id=?');
     $interactionQuery->bindValue(1,$linkID,IntegerParameter::INTEGER);
     $interactionQuery->execute();
     $interactionResult=$interactionQuery->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
     if((!isset(ProteinScoreCalculator::$fullProteinList[$interactionResult[0]['actorAId']])) or (!isset(ProteinScoreCalculator::$fullProteinList[$interactionResult[0]['actorBId']])))
     {echo("Protein for interaction ".$linkID." not found in database (or no localization info), skipping.\n");return 0;}
     $startLocScores=ProteinScoreCalculator::$fullProteinList[$interactionResult[0]['actorAId']]->getMyLocationScores;
     $startNeighborLocScores=ProteinScoreCalculator::$fullProteinList[$interactionResult[0]['actorAId']]->getNeighborLocationScores;
     $endLocScores=ProteinScoreCalculator::$fullProteinList[$interactionResult[0]['actorBId']]->getMyLocationScores;
     $endNeighborLocScores=ProteinScoreCalculator::$fullProteinList[$interactionResult[0]['actorBId']]->getNeighborLocationScores;

     $linkLocalizationScores=array();
     foreach ($this->largeLocs as $localization)
     {
      $startCorrected=ApplyNeigborCorrection($startLocScore[$localization],$startNeighborLocScore[$localization],ProteinScoreCalculator::$averageValues[$interactionQuery['species']][$localization],$this->predictionPower);
      $endCorrected=ApplyNeigborCorrection($endLocScore[$localization],$endNeighborLocScore[$localization],ProteinScoreCalculator::$averageValues[$interactionQuery['species']][$localization],$this->predictionPower);
      $linkLocalizationScores[$localization]=$startCorrected*$endCorrected;
     }
     $value=1;
     foreach ($this->largeLocs as $localization)
        $value*=(1-$linkLocalizationScores[$localization]);
     $LocalizationConfidence=1-$value;

     //not yet implemented
     $InteractionConfidence=1;

     return $LocalizationConfidence*$InteractionConfidence;
    }

}

class ProteinScoreCalculator
{
  public $id;

  public static $largeLocs=array();
  public static $species=array();
  public static $classWeights;
  public static $fullProteinList;

  private $neighbors;
  private $connection;

  private $entries; //3D:"localization", "classnum" and "number"
  private static $averageValues;
  private static $averageArray;

  private $mySpecies;

  public $locationScores;
  public $neighborlocationScores;

  public static $localizationTranslator;

  public static function RecalcAverage()
  {
        foreach(ProteinScoreCalculator::$species as $spec)
            foreach(ProteinScoreCalculator::$largeLocs as $loc)
            {
                ProteinScoreCalculator::$averageArray[$spec][$loc]["sum"]=0;
                ProteinScoreCalculator::$averageArray[$spec][$loc]["num"]=0;
                ProteinScoreCalculator::$averageValues[$spec][$loc]=0;
            }

       foreach(ProteinScoreCalculator::$fullProteinList as $protein)
       {
        $nlocScores=$protein->getNeighborLocationScores();
        foreach($nlocScores as $loc=>$val)
        {
         ProteinScoreCalculator::$averageArray[$protein->mySpecies][$loc]["sum"]+=$val;
         ProteinScoreCalculator::$averageArray[$protein->mySpecies][$loc]["num"]=+1;
        }
       }

       foreach(ProteinScoreCalculator::$species as $spec)
            foreach(ProteinScoreCalculator::$largeLocs as $loc)
                ProteinScoreCalculator::$averageValues[$spec][$loc]=ProteinScoreCalculator::$averageArray[$protein->mySpecies][$loc]["sum"]/ProteinScoreCalculator::$averageArray[$protein->mySpecies][$loc]["num"];
  }

  public function addEntries($SqlAssocArray)
  {
   $firstrow=true;
   foreach ($SqlAssocArray as $row)
   {
    if($firstrow)
    {
     $firstrow=false;
     $this->mySpecies=$row['specieId'];
     if(!in_array($this->mySpecies,ProteinScoreCalculator::$species,true))
        ProteinScoreCalculator::$species[]=$this->mySpecies;
     $this->id=$row['proteinId'];
    }
    $loc=ProteinScoreCalculator::$localizationTranslator->getLargelocById($row['localizationId']);
//    $ProteinCount[$this->mySpecies][$loc][$this->id]=1;
    //if (!isset($this->entries)) $this->entries=array();
    //if (!isset($this->entries[!localization!])) $this->entries[!localization!]=array();
    if (!isset($this->entries[$loc][$row['confidenceType']])) $this->entries[$loc][$row['confidenceType']]=0;
    $this->entries[$loc][$row['confidenceType']]+=1;
   }
   if($firstrow==true) //no localization information, empty set received
    return FALSE;
   //collapse ProteinCount map into a single number
/*   foreach ($ProteinCount as $species=>$ProtCountBySpecies)
    foreach ($ProtCountBySpecies as $largeLoc=>$ProtCountBySpeciesAndLocation)
      $ProtCountBySpeciesAndLocation = count($ProtCountBySpeciesAndLocation);*/

/*   foreach(ProteinScoreCalculator::$averageEntries as $species=>$locationBySpeciesArray)
    foreach($locationBySpeciesArray as $location=>$classByLocationArray)
    {
        foreach($classByLocationArray as $probabilityClass => &$value)
            $value/=$ProteinCount[$species][$location];
        unset($value);
    }*/
    return TRUE;
  }
  public function calculateMyLocationScores()
  {
   $this->locationScores=array();
   foreach(ProteinScoreCalculator::$largeLocs as $localization)
    {
     if(!isset($this->entries[$localization]))
     {$this->locationScores[$localization]=0;continue;}

     $score=1;
     foreach($this->entries[$localization] as $entryClass => $entryNum)
        $score*=pow( 1 - ProteinScoreCalculator::$classWeights[$entryClass] , $entryNum );
     $this->locationScores[$localization]=1 - $score;
    }
  }

  public function addNeighbors($connection)
  {
   $neighborQuery=$connection->prepare("SELECT actorAId AS id FROM Interaction WHERE actorBId=? and actorAId!=actorBId UNION SELECT actorBId AS id FROM Interaction WHERE actorAId=? AND actorAId!=actorBId");
   $neighborQuery->bindValue(1,$this->id,IntegerParameter::INTEGER);
   $neighborQuery->bindValue(2,$this->id,IntegerParameter::INTEGER);
   $neighborQuery->execute();
   $neighbors=$neighborQuery->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
   $this->neighbors=array();
   foreach ($neighbors as $neighbor)
    if(isset(ProteinScoreCalculator::$fullProteinList[$neighbor['id']]))
        $this->neighbors[]=&ProteinScoreCalculator::$fullProteinList[$neighbor['id']];
  }
  public function calculateNeighborLocationScores()
  {
   $this->neighborLocationScores=array();
   foreach (ProteinScoreCalculator::$largeLocs as $localization)
    $this->neighborLocationScores[$localization]=0;
   foreach ($this->neighbors as $neighbor)
   {
    $scores=ProteinScoreCalculator::$fullProteinList[$neighbor['id']]->getMyLocationScores();
    foreach(ProteinScoreCalculator::$largeLocs as $localization)
     $this->neighborLocationScores[$localization]+=$scores[$localization];
   }
   if(count($this->neighbors)>0)
    foreach (ProteinScoreCalculator::$largeLocs as $localization)
     $this->neighborLocationScores[$localization]/=count($this->neighbors);

  }

  public function getMyLocationScores()
  {
   return $this->locationScores;
  }

  public function getNeighborLocationScores()
  {
    return $this->neighborLocationScores;
  }
}
