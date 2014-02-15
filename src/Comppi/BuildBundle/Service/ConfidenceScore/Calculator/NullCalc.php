<?php

namespace Comppi\BuildBundle\Service\ConfidenceScore\Calculator;

class NullCalc implements CalculatorInterface
{
    private $id;

    public function __construct($id) {
        $this->id = $id;
    }

    public function calculate(\Doctrine\DBAL\Connection $connection) {
        $nullInsert = $connection->prepare(
            'INSERT INTO ConfidenceScore(interactionId, calculatorId, score)' .
            ' SELECT id, ?, 0 FROM Interaction'
        );

        $nullInsert->execute(array($this->id));
        
        $nullLocInsert = $connection->prepare(
        	'INSERT INTO LocalizationScore(localizationId, calculatorId, score)' .
        	' SELECT id, ?, 0 FROM ProteinToLocalization'
        );
        
        $nullLocInsert->execute(array($this->id));
        
        $nullAvgInsert = $connection->prepare(
        	'INSERT INTO LocalizationScoreAvg(proteinId, calculatorId, avgScore)' .
        	' SELECT id, ?, 0 FROM Protein'	
        );
        
        $nullAvgInsert->execute(array($this->id));
    }

    public function getName() {
        return "Null Calculator";
    }
}