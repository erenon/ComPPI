<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractLoadCommand extends ContainerAwareCommand
{
    /**
     * @var Comppi\BuildBundle\Service\SpecieProvider\SpecieDescriptor
     */
    protected $specie;

    /**
     * DatabaseProvider service
     * @var Comppi\BuildBundle\Service\DatabaseProvider
     */
    protected $databaseProvider;

    /**
     * Databases to load
     * @var \Iterable
     */
    protected $databases;

    /**
     * Used entity (table) names without specie prefix
     * in the following form:
     * EntityName => Permission
     * where Permission = [READ|WRITE].
     *
     * @example Interaction => WRITE
     * @var array
     */
    protected $usedEntities = array();

    /**
     * ProteinTranslator service
     * @var Comppi\BuildBundle\Service\ProteinTranslator
     */
    protected $proteinTranslator;

    /**
     * LocalizationTranslator service
     * @var Comppi\BuildBundle\Service\LocalizationTranslator
     */
    protected $localizationTranslator;

    /**
     * @var Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * Name of the command: comppi:build:<name>
     * @abstract
     * @var string
     */
    protected $commandName;

    protected function configure() {
        $this
            ->setName('comppi:build:' . $this->commandName)
            ->addArgument('specie', InputArgument::REQUIRED, 'Abbreviation of specie  to load')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        // set specie
        $specieAbbr = $input->getArgument('specie');
        if (!$specieAbbr) {
            throw new \Exception("Please specify a specie! Species availabe: ce, dm, hs, sc");
        }

        $container = $this->getContainer();
        $specieProvider = $container->get('comppi.build.specieProvider');

        $this->specie = $specieProvider->getSpecieByAbbreviation($specieAbbr);

        $this->databaseProvider = $container->get('comppi.build.databaseProvider');

        // setup translators
        $this->proteinTranslator = $container->get('comppi.build.proteinTranslator');
        $this->localizationTranslator = $container->get('comppi.build.localizationTranslator');

        // setup database connection
        $this->connection = $container
            ->get('doctrine.orm.default_entity_manager')
            ->getConnection();

        // avoid memory leak
        $this->connection->getConfiguration()->setSQLLogger(null);
    }

    /**
     * @see addDatabaseRefToId
     * @var Doctrine\DBAL\Driver\Statement
     */
    protected $addDatababaseRefStatement = null;

    /**
     * @TODO This method uses a mysql specific ON DUPLICATE KEY UPDATE clause
     * This could be substituted with a select and a conditional insert
     *
     * @param string $sourceDb
     * @param string $comppiId
     * @param int $specie
     */
    protected function addDatabaseRefToId($sourceDb, $comppiId) {
        if ($this->addDatababaseRefStatement == null) { // init statement
            // insert ref only if not yet inserted
            $statement = 'INSERT INTO ProteinToDatabase' .
                ' VALUES (?, ?)'.
                ' ON DUPLICATE KEY UPDATE proteinId=proteinId';

            $this->addDatababaseRefStatement = $this->connection->prepare($statement);
        }

        $this->addDatababaseRefStatement->bindValue(1, $comppiId);
        $this->addDatababaseRefStatement->bindValue(2, $sourceDb);
        $this->addDatababaseRefStatement->execute();
    }

    protected function openConnection() {
        $this->disableTableKeys();
        $this->disableForeignKeys();
        $this->lockTables();
    }

    protected function closeConnection() {
        $this->unlockTables();
        $this->enableForeignKeys();
        $this->enableTableKeys();
    }

    private function disableTableKeys() {
        foreach ($this->usedEntities as $entity => $permission) {
            if ($permission == 'WRITE') {
                $this->connection->exec('ALTER TABLE ' . $entity . ' DISABLE KEYS');
            }
        }
    }

    private function enableTableKeys() {
        foreach ($this->usedEntities as $entity => $permission) {
            if ($permission == 'WRITE') {
                $this->connection->exec('ALTER TABLE ' . $entity . ' ENABLE KEYS');
            }
        }
    }

    private function disableForeignKeys() {
        $this->connection->exec('SET FOREIGN_KEY_CHECKS = 0;');
    }

    private function enableForeignKeys() {
        $this->connection->exec('SET FOREIGN_KEY_CHECKS = 1;');
    }

    private function lockTables() {
        $tablesWithPermission = array();
        foreach ($this->usedEntities as $entity => $permission) {
            $tablesWithPermission[] = $entity . ' ' . $permission;
        }
        $tableList = implode(', ', $tablesWithPermission);

        $query = 'LOCK TABLES ' . $tableList;
        $this->connection->exec($query);
    }

    private function unlockTables() {
        $query = 'UNLOCK TABLES;';
        $this->connection->exec($query);
    }
}