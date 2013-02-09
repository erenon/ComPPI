<?

namespace Comppi\BuildBundle\Service\ConfidenceScore\Calculator;

use Doctrine\DBAL\Types\IntegerType as IntegerParameter;

class ComppiStandard implements CalculatorInterface
{
    private $id;

    private $useLearning=FALSE;
    private $sampleSize=10;
    private $numberOfIterations=10000;
    private $predictionPower=0.5;
    private $weights=array('0'=>0.7,'1'=>0.99,'2'=>0.7);
    private $threshold=0.95;
    private $learningRate=0.01;
    /**
     * @var Comppi\BuildBundle\Service\LocalizationTranslator\LocalizationTranslator
     */
    private $localizationTranslator;

    /**
     * @var array
     */
    
    public function __construct($id) {
        $this->id = $id;
    }

    public function setLocalizationTranslator($translator) {
        $this->localizationTranslator = $translator;
    }

    public function calculate(\Doctrine\DBAL\Connection $connection) {
    
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
            {echo("Warning: No localization information for protein #".$protein['id']."!"); continue;}
         $proteinCalc->addNeighbors();
         ProteinScoreCalculator::$fullProteinList[$protein['id']]=$proteinCalc;
         if($num%10000==0) echo($num."/".count($protIDs)." proteins added.\n");
        }
        
        $this->averageValues=array();

        if($useLearning)
        {
          echo("Executing PLA for weights ({$numberOfIterations} iterations)...\n");
          $learningParams=array();
          $learningParams[]=$this->weights[0];
          $learningParams[]=$this->weights[1];
          $learningParams[]=$this->weights[2];
          $learningParams[]=$this->predictionPower;
         $bestParams=$learningParams;
          $bestResult=0;
         while ($iterationNumber<$numberOfIterations)
         {
          $this->weights[0]=$learningParams[0];
          $this->weights[1]=$learningParams[1];
          $this->weights[2]=$learningParams[2];
          $this->predictionPower=$learningParams[3];
          
          ProteinScoreCalculator::$classWeights=$this->weights;
          ProteinScoreCalculator::$predictionPower=$this->predictionPower;

          //new iteration: update the average
          foreach(ProteinScoreCalculator::$fullProteinList as $protein)
           $protein->calculateMyLocationScores();
        
          foreach(ProteinScoreCalculator::$fullProteinList as $protein)
           $protein->calculateNeighborLocationScores();

          ProteinScoreCalculator::RecalcAverage();
          
          $currentResults=array();
          for ($i=0;$i<$SampleSize;$i++)
          {
              $this->weights[0]=$learningParams[0];
              $this->weights[1]=$learningParams[1];
              $this->weights[2]=$learningParams[2];
              $this->predictionPower=$learningParams[3];

              $which=rand(0,count($dataSet));
              $interactionQuery=$connection->prepare('SELECT actorAId,actorBId,specieId FROM Interaction LEFT JOIN Protein on Interaction.actorAId=Protein.id WHERE id=?');
              $interactionQuery->bindValue(1,$which,IntegerParameter::INTEGER);
              $interactionQuery->execute();
              $sampleRow=$interactionQuery->fetchRow(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
              //we use the new weights for protein score calculation in-loop, we just don't update the average
              ProteinScoreCalculator::$fullProteinList[$sampleRow['actorAId']]->calculateMyLocationScores();
              ProteinScoreCalculator::$fullProteinList[$sampleRow['actorBId']]->calculateNeighborLocationScores();
              $result=CalculateLinkConfidence($which['link']);
              //classify based on threshold
              if($result>$this->threshold)
                $result=1;
              else $result=0;
              //check hit or miss
              if($result==$dataSet[$which])
                $currentResults[]=1;
              else $currentResults[]=-1;
              //weight update
              foreach($learningParams as &$parameter)
               $parameter+=$alpha*$parameter*$currentResults[count($currentResults)-1];
              unset($parameter);
          }
          if(sum($currentResults)>$bestResult)
            {
             $bestParams=$learningParams;
             $bestResult=sum($currentResults);
            }

         if($iterationNumber%100==0) echo($iterationNumber."/".$numberOfIterations." iterations complete...\n"); 
         }
        }

        ProteinScoreCalculator::$classWeights[0]=$this->weights[0]=$bestParams[0];
        ProteinScoreCalculator::$classWeights[1]=$this->weights[1]=$bestParams[1];
        ProteinScoreCalculator::$classWeights[2]=$this->weights[2]=$bestParams[2];
        ProteinScoreCalculator::$predictionPower=$this->predictionPower=$bestParams[3];        
        
        echo("Calculating final confidence score...\n");
        foreach($this->proteinList as $protein)
         $protein->calculateMyLocationScores();
        
        foreach($this->proteinList as $protein)
         $protein->calculateNeighborLocationScores();
         
        ProteinScoreCalculator::RecalcAverage();
        
        $insert = $connection->prepare(
            'INSERT INTO ConfidenceScore(interactionId, calculatorId, score)' .
            ' VALUES(?, ?, ?)'
        );
        
        $insert->bindValue(2, $this->id, IntegerParameter::INTEGER);

        //no cross-species links
        $interactionSelect = $connection->prepare(
            'SELECT Interaction.id as id ORDER BY id ASC LIMIT ?, ?'
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
        
        echo("Confidence calculation complete...\n");
    }

    public function getName() {
        return "ComPPI Standard";
    }

    private function initCalculation(\Doctrine\DBAL\Connection $connection) {
        // @TODO this hack is required here because of a PDO bug
        // https://bugs.php.net/bug.php?id=44639
        $connection->getWrappedConnection()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        
        ProteinScoreCalculator::$localizationTranslator=$this->localizationTranslator;
        ProteinScoreCalculator::$largeLocs=$this->largeLocs=$this->localizationTranslator->getLargelocs();
        }

    private function ApplyNeigborCorrection($myLocScore, $neighborLocScore, $averageNeighborLocScore, $predictionPower) {

        if ($neighborLocScore < $averageNeighborLocScore) {
            return $myLocScore * (1 - $predictionPower + ($predictionPower * $neighborLocSvore / $averageNeighborLocScore));
        } else {
            return 1- (1-$myLocScore)*(1- ($predictionPower * ($neighborLocScore - $averageNeighborLocScore) / (1 - $averageNeighborLocScore)) );
        }
    }
    
    private function CalculateLinkConfidence($linkID)
    {
     $interactionQuery=$connection->prepare('SELECT actorAId,actorBId,specieId FROM Interaction LEFT JOIN Protein on Interaction.actorAId=Protein.id WHERE id=?');
     $interactionQuery->bindValue(1,$linkID,IntegerParameter::INTEGER);
     $interactionQuery->execute();
     $interactionQuery->fetchRow(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
     $startLocScores=$this->proteinList[$interactionQuery['actorAId']]->getMyLocationScores;
     $startNeighborLocScores=$this->proteinList[$interactionQuery['actorAId']]->getNeighborLocationScores;
     $endLocScores=$this->proteinList[$interactionQuery['actorBId']]->getMyLocationScores;
     $endNeighborLocScores=$this->proteinList[$interactionQuery['actorBId']]->getNeighborLocationScores;
     
     $linkLocalizationScores=array();
     foreach ($this->largeLocs as $localization)
     {
      $startCorrected=ApplyNeigborCorrection($startLocScore[$localization],$startNeighborLocScore[$localization],ProteinScoreCalculator::$averageValues[$interactionQuery['species']][$localization],$predictionPower);
      $endCorrected=ApplyNeigborCorrection($endLocScore[$localization],$endNeighborLocScore[$localization],ProteinScoreCalculator::$averageValues[$interactionQuery['species']][$localization],$predictionPower);
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
                $averageArray[$spec][$loc]["sum"]=0;
                $averageArray[$spec][$loc]["num"]=0;
                $averageValues[$spec][$loc]=0;
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
    echo($row['proteinId']."\n");
    if($firstrow)
    {
     $firstrow=false;
     $this->mySpecies=$row['specieId'];
     if(!in_array($this->mySpecies,ProteinScoreCalculator::$species,true))
        ProteinScoreCalculator::$species[]=$this->mySpecies;
     $this->id=$row['proteinId'];
    }
    $loc=ProteinScoreCalculator::$localizationTranslator->getLargelocById($row['localizationId']);
    $ProteinCount[$this->mySpecies][$loc][$id]=1;
    //if (!isset($this->entries)) $this->entries=array();
    //if (!isset($this->entries[!localization!])) $this->entries[!localization!]=array();
    if (!isset($this->entries[$loc][$row['confidenceType']])) $this->entries[$loc][$row['confidenceType']]=0;
    $this->entries[$loc][$row['confidenceType']]+=1;
    // also add entry to averages
    //if (!isset($this->averageEntries)) $this->averageEntries=array();
    //if (!isset($this->averageEntries[$this->mySpecies])) $this->averageEntries[$this->mySpecies]=array();
    //if (!isset($this->averageEntries[$this->mySpecies][!localization!])) $this->averageEntries[$this->mySpecies][!localization!]=array();
    if (!isset(ProteinScoreCalculator::$averageEntries[$this->mySpecies][$loc][$row['confidenceType']])) $this->averageEntries[$this->mySpecies][$loc][$row['confidenceType']]=0;
    ProteinScoreCalculator::$averageEntries[$this->mySpecies][$loc][$row['confidenceType']]+=1;
   }
   if($firstrow==true) //no localization information, empty set received
    return FALSE;
   //collapse ProteinCount map into a single number
   foreach ($ProteinCount as $species=>$ProtCountBySpecies)
    foreach ($ProtCountBySpecies as $largeLoc=>$ProtCountBySpeciesAndLocation)
      $ProtCountBySpeciasAndLocation = count($ProtCountBySpeciasAndLocation);
   
   foreach(ProteinScoreCalculator::$averageEntries as $species=>$locationBySpeciesArray)
    foreach($locationBySpeciesArray as $location=>$classByLocationArray)
    {
        foreach($classByLocationArray as $probabilityClass => &$value)
            $value/=$ProteinCount[$species][$location];
        unset($value);
    }
    return TRUE;
  }
  public function calculateMyLocationScores()
  {
   $this->locationScores=array();
   foreach($this->largeLocs as $localization)
    {
     $score=1;
     foreach($this->entries[$localization] as $entryClass => $entryNum)
        $score*=pow( 1 - $this->classWeights[$entryClass] , $entryNum );
     $this->locationScores[$localization]=1 - $score;
    }
  }

  public function addNeighbors()
  {
   $neighborQuery=$connection->prepare("SELECT actorAId AS id FROM Interaction WHERE actorBId=? and actorAId!=actorBId UNION SELECT actorBId AS id FROM Interaction WHERE actorAId=? AND actorAId!=actorBId");
   $neighborQuery->bindValue(1,$this->id,IntegerParameter::INTEGER);
   $neighborQuery->bindValue(2,$this->id,IntegerParameter::INTEGER);
   $neighborQuery->execute();
   $neighbors=$neighborQuery->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
   foreach ($neighbors as $neighbor)
   $this->neighbors[]=&ProteinScorecalculator::$protList[$neighbor['id']];
  }
  public function calculateNeighborLocationScores()
  {   
   $this->neighborLocationScores=array();
   foreach ($this->largeLocs as $localization)
    $this->neighborLocationScores[$localization]=0;
   foreach ($this->neighbors as $neighbor)
   {    
    $scores=$this->protList[$neighbor['id']]->getMyLocationScores();
    foreach($this->largeLocs as $localization)
     $this->neighborLocationScores[$localization]+=$scores[$localization];
   }
   if(count($this->neighbors)>0)
    foreach ($this->largeLocs as $localization)
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
