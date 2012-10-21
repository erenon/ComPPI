<?php

namespace Comppi\StatBundle\Service\Search;

class Search
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    protected $connection;

    protected $species = array(
        'hs', 'dm', 'ce', 'sc'
    );

    public function __construct($em) {
        $this->connection = $em->getConnection();
    }

    public function getExamples() {
        $selExamples = $this->connection->executeQuery(
            'SELECT namingConvention, name FROM NameToProteinHs GROUP BY namingConvention LIMIT 5;'
        );

        return $selExamples->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function searchByName($name) {
        $results = array();

        foreach ($this->species as $specie) {
            $main = array();
            $synonyms = array();

            $table = 'Protein' . ucfirst($specie);
            $statement = 'SELECT id as proteinId, proteinName as name, proteinNamingConvention as namingConvention FROM ' . $table .
            	' WHERE proteinName = ?;';

            $select = $this->connection->prepare($statement);
            $select->bindValue(1, $name);
            $select->execute();

            if ($select->rowCount() > 0) {
                $main = $select->fetchAll(\PDO::FETCH_ASSOC);
            }

            $table = 'NameToProtein' . ucfirst($specie);
            $statement = 'SELECT namingConvention, name, proteinId FROM ' . $table .
            	' WHERE name = ?;';

            $select = $this->connection->prepare($statement);
            $select->bindValue(1, $name);
            $select->execute();

            if ($select->rowCount() > 0) {
               $synonyms  = $select->fetchAll(\PDO::FETCH_ASSOC);
            }

            if (!empty($main) || !empty($synonyms)) {
                $results[$specie] = array_merge($main, $synonyms);
            }
        }

        return $results;
    }
}