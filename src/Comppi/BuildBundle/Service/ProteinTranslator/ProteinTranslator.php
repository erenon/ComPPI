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
        'UniProtFull',
        'UniProtKB/Swiss-Prot',
        'UniProtKB/TrEmbl',
        'UniProtKB-AC',
        'UniProtKB-ID',
        'UniProtAlt',
        'EnsemblGeneId',
        'EntrezGene',
        'refseq',
        'WBGeneId',
        'EnsemblPeptideId',
        'Hprd'
    );

    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     *
     * @var Comppi\BuildBundle\Service\ProteinTranslator\NameCache
     */
    private $nameCache;

    public function __construct($em) {
        $this->connection = $em->getConnection();
        $this->nameCache = new NameCache();
    }

    /**
     * Gets an existing ComppiId
     *
     * @param string $namingConvention
     * @param string $originalName
     * @param int $specieId
     * @return int CommpiId
     */
    public function getComppiId($namingConvention, $originalName, $specieId) {
        $comppiId = $this->nameCache->getComppiId($specieId, $namingConvention, $originalName);
        if ($comppiId !== false) {
            return $comppiId;
        }

        $translation = $this->getStrongestTranslation($namingConvention, $originalName, $specieId);
        $comppiId = $this->getExistingComppiId($translation[0], $translation[1], $specieId);

        if ($comppiId === false) {
            $comppiId = $this->insertProtein($translation[0], $translation[1], $specieId);
        }

        $this->nameCache->setComppiId($specieId, $namingConvention, $originalName, $comppiId);

        return $comppiId;
    }

    /**
     * @param string $namingConvention
     * @param string $proteinName
     * @param int $specie
     *
     * @return array 0 => naming convention; 1 => protein name
     */
    private function getStrongestTranslation($namingConvention, $proteinName, $specie) {
        /**
         * @var \Doctrine\DBAL\Driver\Statement
         */
        $translateStatement = $this->connection->prepare(
        	'SELECT namingConventionB, proteinNameB FROM ProteinNameMap' .
            ' WHERE specieId = ? AND namingConventionA = ? AND proteinNameA = ?'
        );
        $translateStatement->execute(array($specie, $namingConvention, $proteinName));
        $translatedNames = $translateStatement->fetchAll();

        // get strongest translated name
        // init strongest translation as the current one
        $strongestTranslation = array($namingConvention, $proteinName);
        $strongestOrder = array_search($namingConvention, $this->namingConventionOrder);

        // convention not found in the order
        // the fixed weakest order (100) is necessary
        if ($strongestOrder === false) {
            $strongestOrder = 100;
        }

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
        /**
         * @var \Doctrine\DBAL\Driver\Statement
         */
        $getIdStatement = $this->connection->prepare(
            'SELECT id FROM Protein' .
            ' WHERE proteinName = ? AND proteinNamingConvention = ? AND specieId = ?' .
            ' LIMIT 1'
        );
        $getIdStatement->execute(array($proteinName, $namingConvention, $specie));

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
            'INSERT INTO Protein' .
            ' VALUES ("", ?, ?, ?)',
            array($specie, $proteinName, $namingConvention)
        );

        return $this->connection->lastInsertId();
    }

    protected function getWeakerSynonyms($namingConvention, $proteinName, $specieId) {
        /**
         * @var \Doctrine\DBAL\Driver\Statement
         */
        $translateStatement = $this->connection->prepare(
        	'SELECT namingConventionA as convention, proteinNameA as name FROM ProteinNameMap' .
            ' WHERE specieId = ? AND namingConventionB = ? AND proteinNameB = ?'
        );

        $translateStatement->execute(array($specieId, $namingConvention, $proteinName));
        $translatedNames = $translateStatement->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        if (empty($translatedNames)) {
            return array();
        } else {
            return $translatedNames;
        }
    }

    protected function getStrongerSynonyms($namingConvention, $proteinName, $specieId) {
        if ($this->namingConventionOrder[0] == $namingConvention) {
            // no naming convention of higher order available
            return array();
        }

        /**
         * @var \Doctrine\DBAL\Driver\Statement
         */
        $translateStatement = $this->connection->prepare(
        	'SELECT namingConventionB as convention, proteinNameB as name FROM ProteinNameMap' .
            ' WHERE specieId = ? AND namingConventionA = ? AND proteinNameA = ?'
        );

        $translateStatement->execute(array($specieId, $namingConvention, $proteinName));
        $translatedNames = $translateStatement->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        if (empty($translatedNames)) {
            return array();
        } else {
            return $translatedNames;
        }
    }

    public function getSynonyms($namingConvention, $proteinName, $specieId, $synonyms = array()) {
        if (empty($synonyms)) {
            $synonyms[0] = array(
                'convention' => $namingConvention,
                'name' => $proteinName
            );
        }

        $strongerSynonyms = $this->getStrongerSynonyms($namingConvention, $proteinName, $specieId);
        $weakerSynonyms = $this->getWeakerSynonyms($namingConvention, $proteinName, $specieId);

        $foundSynonyms = array_merge($weakerSynonyms, $strongerSynonyms);
        $newSynonyms = array();

        foreach ($foundSynonyms as $foundSynonym) {
            $neededSynonym = true;

            foreach ($synonyms as $synonym) {
                // add only not yet found naming conventions to $newSynonyms
                if ($foundSynonym['convention'] == $synonym['convention']) {
                    $neededSynonym = false;
                    break;
                }
            }

            if ($neededSynonym === true) {
                $newSynonyms[] = $foundSynonym;
            }
        }

        $synonyms = array_merge($synonyms, $newSynonyms);

        foreach ($newSynonyms as $newSynonym) {
            $newSynonyms = array_merge(
                $newSynonyms,
                $this->getSynonyms(
                    $newSynonym['convention'],
                    $newSynonym['name'],
                    $specieId,
                    $synonyms
                )
            );
        }

        return $newSynonyms;
    }
}