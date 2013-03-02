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
        	' WHERE proteinName LIKE ?',
            array('%' . $name . '%')
        );

        if ($select->rowCount() > 0) {
            $results = $select->fetchAll(\PDO::FETCH_ASSOC);

            // exclude synonyms with the same name and same protein
            $synonymInClause = ' AND np.proteinId NOT IN (?)';

            // create the parameter of the IN clause
            $ids = array();
            foreach ($results as $result) {
                $ids[] = $result['proteinId'];
            }
            $synonymParameters = array('%' . $name . '%', join(',',$ids));
        } else {
            // no protein found yet, IN clause not needed
            $synonymInClause = '';
            $synonymParameters = array('%' . $name . '%');
        }


        $select = $this->connection->executeQuery(
            'SELECT np.proteinId, np.specieId, p.proteinName as name, p.proteinNamingConvention as namingConvention, np.name as altName, np.namingConvention as altConvention' .
            ' FROM NameToProtein np' .
            ' LEFT JOIN Protein p ON p.id = np.proteinId' .
            ' WHERE name LIKE ?' .
            $synonymInClause .
            ' GROUP BY np.proteinId',
            $synonymParameters
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

    public function getNamesContaining($needle) {
        $select = $this->connection->executeQuery(
        	'SELECT name FROM ProteinName' .
        	' WHERE name LIKE ?' .
            ' ORDER BY LENGTH(name)' .
            ' LIMIT 10',
            array('%' . $needle . '%')
        );

        if ($select->rowCount() > 0) {
            $results = $select->fetchAll(\PDO::FETCH_ASSOC);
            return $results;
        }

        return array();
    }
}