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
     * Gets matching ComppiIds
     *
     * @param string $namingConvention
     * @param string $originalName
     * @param int $specieId
     * @return array CommpiId
     */
    public function getComppiIds($namingConvention, $originalName, $specieId) {
        $comppiIds = $this->nameCache->getComppiIds($specieId, $namingConvention, $originalName);
        if ($comppiIds !== false) {
            return $comppiIds;
        }

        $translations = $this->getStrongestTranslations($namingConvention, $originalName, $specieId);

        $comppiIds = array();
        foreach ($translations as $translation) {
            $comppiId = $this->getExistingComppiId($translation[0], $translation[1], $specieId);

            if ($comppiId === false) {
                $comppiId = $this->insertProtein($translation[0], $translation[1], $specieId);
            }

            $comppiIds[] = $comppiId;
        }

        $this->nameCache->setComppiIds($specieId, $namingConvention, $originalName, $comppiIds);

        return $comppiIds;
    }

    /**
     * @param string $namingConvention
     * @param string $proteinName
     * @param int $specie
     *
     * @return array 0 => naming convention; 1 => protein name
     */
    private function getStrongestTranslations($namingConvention, $proteinName, $specie) {
        /**
         * @var \Doctrine\DBAL\Driver\Statement
         */
        $translateStatement = $this->connection->prepare(
        	'SELECT namingConventionB, proteinNameB FROM ProteinNameMap' .
            ' WHERE specieId = ? AND namingConventionA = ? AND proteinNameA = ?'
        );
        $translateStatement->execute(array($specie, $namingConvention, $proteinName));
        $translatedNames = $translateStatement->fetchAll();

        // init strongest translation order to the current order
        $strongestOrder = array_search($namingConvention, $this->namingConventionOrder);

        // convention not found in the order
        if ($strongestOrder === false) {
            $strongestOrder = count($this->namingConventionOrder) + 1;
        }

        // get strongest translated name
        $translations = array();

        foreach ($translatedNames as $translatedName) {
            $translatedNameOrder = array_search(
                $translatedName['namingConventionB'],
                $this->namingConventionOrder
            );

            if ($translatedNameOrder < $strongestOrder) {
                // stronger translation found
                $strongestOrder = $translatedNameOrder;

                // discard previous translations
                $translations = array();

                // add translation
                $translations[] = array(
                    $translatedName['namingConventionB'],
                    $translatedName['proteinNameB']
                );

            } else if ($translatedNameOrder == $strongestOrder) {
                // == : allow conventionA -> conventionA style maps
                // this is required because of the UniProtKB-AC secondary names

                // add translation
                $translations[] = array(
                    $translatedName['namingConventionB'],
                    $translatedName['proteinNameB']
                );

            } // else: translation is weaker, do nothing (discard)
        }

        if (empty($translations) === false) { // translation found
            // try to get stronger translations using recursion
            $strongerTranslations = array();

            foreach ($translations as $translation) {
                $strongerTranslations = array_merge(
                    $strongerTranslations,
                    $this->getStrongestTranslations(
                        $translation[0],
                        $translation[1],
                        $specie
                    )
                );
            }

            return $strongerTranslations;
        } else {
            // no stronger translation found
            // return the one we got
            return array(array($namingConvention, $proteinName));
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