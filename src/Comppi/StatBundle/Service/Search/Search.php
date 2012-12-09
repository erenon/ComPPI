<?php

namespace Comppi\StatBundle\Service\Search;

class Search
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * @var Comppi\BuildBundle\Service\SpecieProvider\SpecieProvider
     */
    protected $specieProvider;

    public function __construct($em, $specieProvider) {
        $this->connection = $em->getConnection();
        $this->specieProvider = $specieProvider;
    }

    public function getExamples() {
        $selExamples = $this->connection->executeQuery(
            'SELECT namingConvention, name FROM NameToProtein GROUP BY namingConvention LIMIT 5;'
        );

        return $selExamples->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function searchByName($name) {
        $results = array();

        $select = $this->connection->executeQuery(
        	'SELECT id as proteinId, specieId, proteinName as name, proteinNamingConvention as namingConvention FROM Protein' .
        	' WHERE proteinName = ?',
            array($name)
        );

        if ($select->rowCount() > 0) {
            $results = $select->fetchAll(\PDO::FETCH_ASSOC);
        }

        $select = $this->connection->executeQuery(
            'SELECT proteinId, specieId, name, namingConvention FROM NameToProtein' .
        	' WHERE name = ?',
            array($name)
        );

        if ($select->rowCount() > 0) {
           $synonyms  = $select->fetchAll(\PDO::FETCH_ASSOC);
           $results = array_merge($results, $synonyms);
        }

        // change specie ids to specie descriptors
        $specieDescriptors = $this->specieProvider->getDescriptors();

        foreach ($results as &$result) {
            $result['specie'] = $specieDescriptors[$result['specieId']];
        }

        return $results;
    }
}