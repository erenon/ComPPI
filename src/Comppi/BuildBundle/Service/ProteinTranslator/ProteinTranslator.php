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
        'UniProtFull',
        'UniProtKB/Swiss-Prot',
        'UniProtKB/TrEmbl',
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

        if (
            $strongestTranslation[0] != $namingConvention // stronger translation found
        ) {
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

    /**
     * This variable should be passed around by _getSynonyms
     * but it's implemented as a member because of performance considerations.
     *
     * @var array
     */
    protected $synonyms;

    /**
     * This variable should be passed around by _getSynonyms
     * but it's implemented as a member because of performance considerations.
     *
     * @var array
     */
    protected $foundConventions;

    protected function _getSynonyms($namingConvention, $proteinName, $specieId, $synonyms = array()) {
        $strongerSynonyms = $this->getStrongerSynonyms($namingConvention, $proteinName, $specieId);
        $weakerSynonyms = $this->getWeakerSynonyms($namingConvention, $proteinName, $specieId);

        $foundSynonyms = array_merge($weakerSynonyms, $strongerSynonyms);
        $newSynonyms = array();

        // add new synonyms to newSynonyms
        foreach ($foundSynonyms as $foundSynonym) {
            if (isset($this->foundConventions[$foundSynonym['convention']]) === false) {
                $newSynonyms[] = $foundSynonym;
            }
        }

        // maintain foundConventions
        // this foreach can't be merged into the previous one
        foreach ($newSynonyms as $newSynonym) {
            if (isset($this->foundConventions[$newSynonym['convention']]) === false) {
                $this->foundConventions[$newSynonym['convention']] = true;
            }
        }

        $this->synonyms = array_merge($this->synonyms, $newSynonyms);

        foreach ($newSynonyms as $newSynonym) {
            $this->_getSynonyms(
                $newSynonym['convention'],
                $newSynonym['name'],
                $specieId
            );
        }
    }

    public function getSynonyms($namingConvention, $proteinName, $specieId) {
        $this->synonyms = array();
        $this->foundConventions = array();

        $this->foundConventions[$namingConvention] = true;

        $this->_getSynonyms($namingConvention, $proteinName, $specieId);

        return $this->synonyms;
    }
}