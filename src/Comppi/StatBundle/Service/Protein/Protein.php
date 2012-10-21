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

    public function get($specie, $id) {
        $table = 'Protein' . ucfirst($specie);
        $statement = 'SELECT proteinName as name, proteinNamingConvention as namingConvention FROM ' . $table .
        	" WHERE id = ? LIMIT 1;";

        $select = $this->connection->prepare($statement);
        $select->bindValue(1, $id);
        $select->execute();

        if ($select->rowCount() > 0) {
            return $select->fetch(\PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    public function getSynonyms($specie, $id) {
        $table = 'NameToProtein' . ucfirst($specie);
        $statement = 'SELECT namingConvention, name FROM ' . $table .
        	" WHERE proteinId = ?;";

        $select = $this->connection->prepare($statement);
        $select->bindValue(1, $id);
        $select->execute();

        if ($select->rowCount() > 0) {
            return $select->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    public function getLocalizations($specie, $id) {
        $table = 'ProteinToLocalization' . ucfirst($specie);
        $statement = 'SELECT localizationId as id, sourceDb, pubmedId, experimentalSystemType FROM ' . $table .
        	" WHERE proteinId = ?;";

        $select = $this->connection->prepare($statement);
        $select->bindValue(1, $id);
        $select->execute();

        if ($select->rowCount() > 0) {
            return $select->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    public function getInteractions($specie, $id) {
        $table = 'Interaction' . ucfirst($specie);
        $statement = 'SELECT actorAId, actorBId, sourceDb, pubmedId, experimentalSystemType FROM ' . $table .
        	" WHERE actorAId = ? OR actorBId = ?;";

        $select = $this->connection->prepare($statement);
        $select->bindValue(1, $id);
        $select->bindValue(2, $id);
        $select->execute();

        if ($select->rowCount() > 0) {
            $results = $select->fetchAll(\PDO::FETCH_ASSOC);

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