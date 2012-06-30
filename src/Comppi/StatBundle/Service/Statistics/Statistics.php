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
     * @param string $specie Specie abbreviation
     */
    public function getInteractionSourceStats($specie) {
        $table = 'Interaction' . ucfirst($specie);
        $selInteractionStats = $this->connection->executeQuery(
            "SELECT sourceDb as 'database', COUNT(sourceDb) as interactionCount FROM ".$table." GROUP BY sourceDb ORDER BY interactionCount DESC;"
        );
        $interactionStats = $selInteractionStats->fetchAll(\PDO::FETCH_ASSOC);

        return $interactionStats;
    }

    public function getLocalizationSourceStats($specie) {
        $table = 'ProteinToLocalization' . ucfirst($specie);
        $selLocalizationStats = $this->connection->executeQuery(
            "SELECT sourceDb as 'database', COUNT(sourceDb) as localizationCount FROM ".$table." GROUP BY sourceDb ORDER BY localizationCount DESC;"
        );
        $localizationStats = $selLocalizationStats->fetchAll(\PDO::FETCH_ASSOC);

        return $localizationStats;
    }

    public function getSourceProteinCounts($specie) {
        $table = 'ProteinToDatabase' . ucfirst($specie);
        $selSourceProteinCounts = $this->connection->executeQuery(
            "SELECT sourceDb as 'database', COUNT(sourceDb) as proteinCount FROM ".$table." GROUP BY sourceDb;"
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

    public function getLocalizationStats($specie) {
        $table = 'ProteinToLocalization' . ucfirst($specie);
        $selLocalizationStats = $this->connection->executeQuery(
            "SELECT localizationId, COUNT(localizationId) as proteinCount FROM ".$table." GROUP BY localizationId ORDER BY proteinCount DESC;"
        );
        $localizationStats = $selLocalizationStats->fetchAll(\PDO::FETCH_ASSOC);

        return $localizationStats;
    }

    public function getNamingConventionStats($specie) {
        $table = 'Protein' . ucfirst($specie);
        $selNamingStats = $this->connection->executeQuery(
        	"SELECT proteinNamingConvention as namingConvention, COUNT(proteinNamingConvention) as proteinCount FROM ".$table." GROUP BY proteinNamingConvention ORDER BY proteinCount DESC;"
        );
        $namingStats = $selNamingStats->fetchAll(\PDO::FETCH_ASSOC);

        return $namingStats;
    }

    public function getMapStats($specie) {
        $table = 'ProteinNameMap' . ucfirst($specie);
        $selMapStats = $this->connection->executeQuery(
            "SELECT namingConventionA, namingConventionB, COUNT(*) as proteinCount FROM ".$table." GROUP BY namingConventionA, namingConventionB;"
        );
        $mapStats = $selMapStats->fetchAll(\PDO::FETCH_ASSOC);

        return $mapStats;
    }
}