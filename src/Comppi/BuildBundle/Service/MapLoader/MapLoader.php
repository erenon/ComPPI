<?php

namespace Comppi\BuildBundle\Service\MapLoader;

class MapLoader
{
    private $connection;

    public function __construct($em) {
        $this->connection = $em->getConnection();
    }
    
    public function loadMap($map, $specie) {
        echo 'loading map: ' . get_class($map) . "\n";
        
        $entityName = 'ProteinNameMap' . ucfirst($specie);
        
        $recordsPerTransaction = 1000;
        $recordIdx = 0;
        
        $connection = $this->connection;
        $connection->beginTransaction();
        foreach ($map as $entry) {
            
            $connection->insert($entityName, $entry);
            
            $recordIdx++;
            if ($recordIdx == $recordsPerTransaction) {
                $recordIdx = 0;
                
                $connection->commit();
                $connection->beginTransaction();
                
                echo $recordsPerTransaction . " records loaded \n";
            }
        }
        $connection->commit();
    }
}