<?php

namespace Comppi\BuildBundle\Service\ConfidenceScore\Calculator;

interface CalculatorInterface
{
    public function __construct($id);
    public function calculate(\Doctrine\DBAL\Connection $connection);
}