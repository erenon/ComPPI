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

    public function __construct($em) {
        $this->connection = $em->getConnection();
        $this->systemTypeCache = new SystemTypeCache();

        $this->systemTypeIdByNameSelect = $this->connection->prepare(
            'SELECT id FROM SystemType WHERE name = ?'
        );

        $this->systemTypeInsert = $this->connection->prepare(
        	'INSERT INTO SystemType VALUES ("", ?, ?)'
        );
    }

    public function getSystemTypeId($systemTypeName) {
        // try cache
        $id = $this->systemTypeCache->getSystemTypeId($systemTypeName);

        // cache hit
        if ($id !== false) {
            return $id;
        }

        // cache miss
        // try database
        $this->systemTypeIdByNameSelect->execute(array($systemTypeName));

        if ($this->systemTypeIdByNameSelect->rowCount() > 0) {
            // systemType found in database
            $id = $this->systemTypeIdByNameSelect->fetchColumn(0);
        } else {
            // database miss -> insert unknown system
            $this->systemTypeInsert->execute(array($systemTypeName, 0));
            $id = $this->connection->lastInsertId();
        }

        // update cache
        $this->systemTypeCache->setSystemTypeId($systemTypeName, $id);

        return $id;
    }
}