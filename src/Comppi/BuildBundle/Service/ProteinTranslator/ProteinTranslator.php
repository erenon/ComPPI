<?php

namespace Comppi\BuildBundle\Service\ProteinTranslator;

class ProteinTranslator
{
    /**
     * Precedence order of naming conventions.
     * Strongest first. 
     * @var array
     */
    private $namingConventionOrder = array(
        'UniProtKB-AC',
        'UniProtKB-ID',
        'EntrezGene'
    );
    
    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;
    
    public function __construct($em) {
        $this->connection = $em->getConnection();    
    }
    
    /**
     * Gets an existing ComppiId 
     * 
     * @param string $namingConvention
     * @param string $originalName
     * @param string $specie
     * @return int|bool CommpiId or false if protein doesn't found
     */
    public function getComppiId($namingConvention, $originalName, $specie) {
        $translation = $this->getStrongestTranslation($namingConvention, $originalName, $specie);
        $comppiId = $this->getExistingComppiId($translation[0], $translation[1], $specie);
        
        if ($comppiId === false) {
            $comppiId = $this->insertProtein($translation[0], $translation[1], $specie); 
        }
        
        return $comppiId;
    }
    
    /**
     * @param string $namingConvention
     * @param string $proteinName
     * @param string $specie
     * 
     * @return array 0 => naming convention; 1 => protein name
     */
    private function getStrongestTranslation($namingConvention, $proteinName, $specie) {
        $mapTableName = 'ProteinNameMap' . ucfirst($specie);
        
        /**
         * @var \Doctrine\DBAL\Driver\Statement
         */
        $translateStatement = $this->connection->prepare(
        	'SELECT namingConventionB, proteinNameB FROM ' . $mapTableName .
            ' WHERE namingConventionA = ? AND proteinNameA = ?'
        );
        $translateStatement->execute(array($namingConvention, $proteinName));
        $translatedNames = $translateStatement->fetchAll();
        
        // get strongest translated name
        $strongestOrder = array_search($namingConvention, $this->namingConventionOrder);
        $strongestTranslation = array($namingConvention, $proteinName);
        
        foreach ($translatedNames as $translatedName) {
            $translatedNameOrder = array_search(
                $translatedName['namingConventionB'], 
                $this->namingConventionOrder
            );
            
            if ($translatedNameOrder < $strongestOrder) {
                $strongestOrder = $translatedNameOrder;
                $strongestTranslation = array(
                    $translatedName['namingConventionB'],
                    $translatedName['proteinNameB']
                );
            }
        }
        
        if ($strongestTranslation[0] != $namingConvention) {
            // stronger translation found
            // try to get an even more stronger one
            // using recursion
            return $this->getStrongestTranslation(
                $strongestTranslation[0],
                $strongestTranslation[1], 
                $specie
            );
        } else {
            // no stronger translation found
            return $strongestTranslation;
        }
    }
    
    private function getExistingComppiId($namingConvention, $proteinName, $specie) {
        $proteinTableName = 'Protein' . ucfirst($specie);
        /**
         * @var \Doctrine\DBAL\Driver\Statement
         */
        $getIdStatement = $this->connection->prepare(
            'SELECT id FROM ' . $proteinTableName .
            ' WHERE proteinName = ? AND proteinNamingConvention = ?' .
            ' LIMIT 1'
        );
        $getIdStatement->execute(array($proteinName, $namingConvention));
        
        if ($getIdStatement->rowCount() > 0) {
            $result = $getIdStatement->fetch();
            
            /** @TODO remove next debug info line */
            //echo 'Existing comppiid found: ' . $result['id'] . "\n";
            
            return $result['id'];
        } else {
            return false;
        }
    }
    
    private function insertProtein($namingConvention, $proteinName, $specie) {
        $proteinTableName = 'Protein' . ucfirst($specie);
        
        $this->connection->executeQuery(
            'INSERT INTO ' .$proteinTableName.
            ' VALUES ("", ?, ?)',
            array($proteinName, $namingConvention)
        );
        
        return $this->connection->lastInsertId();
    }
}