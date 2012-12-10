<?php

namespace Comppi\BuildBundle\Service\ConfidenceScore;

use Comppi\BuildBundle\Service\ConfidenceScore\Calculator\NullCalc;

class ConfidenceScore
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;

    const NULL_CALC = 0;

    private $calculators = array();

    public function __construct($em) {
        $this->connection = $em->getConnection();

        $this->calculators[self::NULL_CALC] = new Calculator\NullCalc(self::NULL_CALC);
    }

    public function calculateScores($calculatorId) {
        if (!isset($this->calculators[$calculatorId])) {
            throw new \InvalidArgumentException("Invalid calculatorId given");
        }

        $this->calculators[$calculatorId]->calculate($this->connection);
    }

    public function getCalculatorName($calculatorId) {
        if (!isset($this->calculators[$calculatorId])) {
            throw new \InvalidArgumentException("Invalid calculatorId given");
        }

        return $this->calculators[$calculatorId]->getName();
    }
}