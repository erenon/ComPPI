<?php

namespace Comppi\BuildBundle\Service\SystemTypeTranslator;

class SystemTypeTranslator
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var Comppi\BuildBundle\Service\SystemTypeTranslator\SystemTypeCache
     */
    private $systemTypeCache;

    /**
     * @var \Doctrine\DBAL\Driver\Statement
     */
    private $systemTypeIdByNameSelect;

    /**
     * @var \Doctrine\DBAL\Driver\Statement
     */
    private $systemTypeInsert;

    /**
     * synonym => main name
     * @var array
     */
    private $synonyms;

    /**
     * Path to the system type descriptor file
     * @var string
     */
    private $systemFile;

    public function __construct($em, $synonymFile, $systemFile) {
        $this->connection = $em->getConnection();
        $this->systemTypeCache = new SystemTypeCache();

        $this->systemTypeIdByNameSelect = $this->connection->prepare(
            'SELECT id FROM SystemType WHERE name = ?'
        );

        $this->systemTypeInsert = $this->connection->prepare(
        	'INSERT INTO SystemType VALUES ("", ?, ?)'
        );

        $this->loadSynonyms($synonymFile);

        $this->systemFile = $systemFile;
    }

    public function loadSystems() {
        $handle = fopen($this->systemFile, 'r');

        if ($handle === false) {
            throw new \InvalidArgumentException("Failed to open systemFile: '" . $this->systemFile . "'");
        }

        $this->connection->beginTransaction();

        while (($row = fgetcsv($handle, 0, ";")) !== false) {
            $this->systemTypeInsert->execute(array(
                $row[0],
                $row[1]
            ));
        }

        $this->connection->commit();
    }

    public function getSystemTypeId($systemTypeName) {
        // try cache
        $id = $this->systemTypeCache->getSystemTypeId($systemTypeName);

        // cache hit
        if ($id !== false) {
            return $id;
        }

        $mainName = isset($this->synonyms[$systemTypeName])
            ? $this->synonyms[$systemTypeName]
            : $systemTypeName;

        // cache miss
        // try database
        $this->systemTypeIdByNameSelect->execute(array($mainName));

        if ($this->systemTypeIdByNameSelect->rowCount() > 0) {
            // systemType found in database
            $id = $this->systemTypeIdByNameSelect->fetchColumn(0);
        } else {
            // database miss -> insert unknown system
            $this->systemTypeInsert->execute(array($mainName, 0));
            $id = $this->connection->lastInsertId();
        }

        // update cache
        $this->systemTypeCache->setSystemTypeId($systemTypeName, $id);

        return $id;
    }

    private function loadSynonyms($synonymFile) {
        $this->synonyms = array();

        $handle = fopen($synonymFile, 'r');

        if ($handle === false) {
            throw new \InvalidArgumentException("Failed to open synonymFile: '" . $synonymFile . "'");
        }

        while (($row = fgetcsv($handle, 0, ";")) !== false) {
            if (is_array($row)) {
                $mainName = array_shift($row);

                $this->synonyms[$mainName] = $mainName;

                foreach ($row as $synonym) {
                    $this->synonyms[$synonym] = $mainName;
                }
            }
        }

        fclose($handle);
    }
}