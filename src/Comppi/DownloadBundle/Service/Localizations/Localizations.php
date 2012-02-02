<?php

namespace Comppi\DownloadBundle\Service\Localizations;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

class Localizations
{
    /**
     * Doctrine connection
     *  
     * @var Doctrine\DBAL\Connection
     */
    protected $connection;
    
    public function __construct(\Doctrine\ORM\EntityManager $em) {
        $this->connection = $em->getConnection();
    }
    
    public function getLocalizations() {
        /** @var $locals Doctrine\DBAL\Driver\Statement */
        $stmt = $this->connection->executeQuery("SELECT DISTINCT prediction FROM EsldbCe");
        $locals = $stmt->fetchAll(Query::HYDRATE_SCALAR);
        
        $passedLocals = array();
        foreach ($locals as $local) {
            if ($local[0] !== '' && $local[0] !== 'None') {
                $passedLocals[] = $local[0];
            }
        } 
        
        return $passedLocals;
    }
    
    public function getInteractions($localization) {
        /** @var $locals Doctrine\DBAL\Driver\Statement */
        $stmt = $this->connection->executeQuery(
            "SELECT B.systematicNameInteractorA, B.systematicNameInteractorB FROM BiogridCe B LEFT JOIN EsldbCe E ON (E.originalDatabaseCode = B.systematicNameInteractorA OR E.originalDatabaseCode = B.systematicNameInteractorB) WHERE E.prediction = :local",
            array('local' => $localization)
        );
        
        $interactions = $stmt->fetchAll(Query::HYDRATE_SCALAR);

        return $interactions;
    }
}