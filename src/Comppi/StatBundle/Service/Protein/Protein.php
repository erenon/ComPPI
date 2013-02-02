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
        	' WHERE proteinId = ?' .
            ' ORDER BY namingConvention',
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
        	'SELECT id, localizationId, sourceDb, pubmedId FROM ProteinToLocalization' .
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
            'SELECT Interaction.id, sourceDb, pubmedId, ' .
            ' Protein.id as actorId, Protein.proteinName as actorName, Protein.proteinNamingConvention as actorNamingConvention' .
            ' FROM Interaction' .
            ' LEFT JOIN Protein ON Protein.id = IF(actorAId = ?, actorBId, actorAId)' .
            ' WHERE actorAId = ? OR actorBId = ?',
            array($id, $id, $id)
        );

        if ($interactions->rowCount() > 0) {
            return $interactions->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    public function getInteractionDetails($interactionId) {
        $systemTypesSel = $this->connection->executeQuery(
        	'SELECT name FROM SystemType' .
        	' LEFT JOIN InteractionToSystemType as ItoS ON SystemType.id = ItoS.systemTypeId' .
        	' WHERE ItoS.interactionId = ?',
            array($interactionId)
        );

        $confidenceScoresSel = $this->connection->executeQuery(
        	'SELECT calculatorId, score FROM ConfidenceScore WHERE interactionId = ?',
            array($interactionId)
        );

        $systemTypes = $systemTypesSel->fetchAll(\PDO::FETCH_ASSOC);
        $confidenceScores = $confidenceScoresSel->fetchAll(\PDO::FETCH_ASSOC);

        return array(
            'systemTypes' => $systemTypes,
            'confidenceScores' => $confidenceScores
        );
    }

    public function getLocalizationDetails($localizationId) {
        $systemTypesSel = $this->connection->executeQuery(
            'SELECT name FROM SystemType' .
            ' LEFT JOIN ProtLocToSystemType as LtoS ON SystemType.id = LtoS.systemTypeid' .
            ' WHERE LtoS.protLocId = ?',
            array($localizationId)
        );

        $systemTypes = $systemTypesSel->fetchAll(\PDO::FETCH_ASSOC);

        return array(
            'systemTypes' => $systemTypes,
        );
    }
}