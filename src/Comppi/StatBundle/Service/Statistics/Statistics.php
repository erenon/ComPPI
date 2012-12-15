<?php

namespace Comppi\StatBundle\Service\Statistics;

class Statistics
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    protected $connection;

    public function __construct($em) {
        $this->connection = $em->getConnection();
    }

    /**
     * Lists the used PPI sources and related interaction counts
     *
     * @param int $specieId
     */
    public function getInteractionSourceStats($specieId) {
        $selInteractionStats = $this->connection->executeQuery(
            "SELECT sourceDb as 'database', COUNT(sourceDb) as interactionCount FROM Interaction" .
            " LEFT JOIN Protein ON Interaction.actorAId = Protein.id" .
        	" WHERE Protein.specieId = ? GROUP BY sourceDb ORDER BY interactionCount DESC;",
        	array($specieId)
        );
        $interactionStats = $selInteractionStats->fetchAll(\PDO::FETCH_ASSOC);

        return $interactionStats;
    }

    public function getLocalizationSourceStats($specieId) {
        $selLocalizationStats = $this->connection->executeQuery(
            "SELECT sourceDb as 'database', COUNT(sourceDb) as localizationCount FROM ProteinToLocalization" .
            " LEFT JOIN Protein ON ProteinToLocalization.proteinId = Protein.id" .
        	" WHERE Protein.specieId = ? GROUP BY sourceDb ORDER BY localizationCount DESC;",
            array($specieId)
        );
        $localizationStats = $selLocalizationStats->fetchAll(\PDO::FETCH_ASSOC);

        return $localizationStats;
    }

    public function getSourceProteinCounts($specieId) {
        $selSourceProteinCounts = $this->connection->executeQuery(
            "SELECT sourceDb as 'database', COUNT(sourceDb) as proteinCount FROM ProteinToDatabase" .
            " LEFT JOIN Protein ON ProteinToDatabase.proteinId = Protein.id" .
        	" WHERE Protein.specieId = ? GROUP BY sourceDb;",
            array($specieId)
        );
        $sourceProteinCounts = $selSourceProteinCounts->fetchAll(\PDO::FETCH_ASSOC);

        // create an associative array
        // sourceDb => proteinCount
        $countsByDatabase = array();

        foreach ($sourceProteinCounts as $record) {
            $countsByDatabase[$record['database']] = $record['proteinCount'];
        }

        return $countsByDatabase;
    }

    public function getLocalizationStats($specieId) {
        $selLocalizationStats = $this->connection->executeQuery(
            "SELECT localizationId, COUNT(localizationId) as proteinCount FROM ProteinToLocalization" .
        	" LEFT JOIN Protein ON ProteinToLocalization.proteinId = Protein.id" .
            " WHERE Protein.specieId = ? GROUP BY localizationId ORDER BY proteinCount DESC;",
            array($specieId)
        );
        $localizationStats = $selLocalizationStats->fetchAll(\PDO::FETCH_ASSOC);

        return $localizationStats;
    }

    public function getNamingConventionStats($specieId) {
        $selNamingStats = $this->connection->executeQuery(
        	"SELECT proteinNamingConvention as namingConvention, COUNT(proteinNamingConvention) as proteinCount FROM Protein" .
        	" WHERE specieId = ? GROUP BY proteinNamingConvention ORDER BY proteinCount DESC;",
            array($specieId)
        );
        $namingStats = $selNamingStats->fetchAll(\PDO::FETCH_ASSOC);

        return $namingStats;
    }

    public function getMapStats($specieId) {
        $selMapStats = $this->connection->executeQuery(
            "SELECT namingConventionA, namingConventionB, COUNT(*) as proteinCount FROM ProteinNameMap" .
        	" WHERE specieId = ? GROUP BY namingConventionA, namingConventionB;",
            array($specieId)
        );
        $mapStats = $selMapStats->fetchAll(\PDO::FETCH_ASSOC);

        return $mapStats;
    }
}