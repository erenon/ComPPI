<?php

namespace Comppi\StatBundle\Service\Protein;

class Protein
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    protected $connection;

    public function __construct($em) {
        $this->connection = $em->getConnection();
    }

    public function get($specieId, $id) {
        $protein = $this->connection->executeQuery(
            'SELECT proteinName as name, proteinNamingConvention as namingConvention FROM Protein' .
        	' WHERE id = ? AND specieId = ? LIMIT 1',
            array($id, $specieId)
        );

        if ($protein->rowCount() > 0) {
            return $protein->fetch(\PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    public function getSynonyms($id) {
        $synonyms = $this->connection->executeQuery(
        	'SELECT namingConvention, name FROM NameToProtein' .
        	' WHERE proteinId = ?',
            array($id)
        );

        if ($synonyms->rowCount() > 0) {
            return $synonyms->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    public function getLocalizations($id) {
        $localizations = $this->connection->executeQuery(
        	'SELECT localizationId as id, sourceDb, pubmedId FROM ProteinToLocalization' .
        	' WHERE proteinId = ?',
            array($id)
        );

        if ($localizations->rowCount() > 0) {
            return $localizations->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    public function getInteractions($id) {
        $interactions = $this->connection->executeQuery(
        	'SELECT actorAId, actorBId, sourceDb, pubmedId FROM Interaction' .
        	' WHERE actorAId = ? OR actorBId = ?',
            array($id, $id)
        );

        if ($interactions->rowCount() > 0) {
            $results = $interactions->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($results as &$record) {
                if ($record['actorAId'] == $id) {
                    $record['actorId'] = $record['actorBId'];
                } else {
                    $record['actorId'] = $record['actorAId'];
                }

                unset($record['actorAId']);
                unset($record['actorBId']);
            }

            return $results;
        } else {
            return false;
        }
    }
}