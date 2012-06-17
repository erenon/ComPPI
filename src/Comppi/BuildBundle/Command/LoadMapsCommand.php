<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadMapsCommand extends AbstractLoadCommand
{
    protected $commandName = 'maps';

    protected $usedEntities = array(
        'Protein' => 'WRITE',
        'ProteinNameMap' => 'WRITE'
    );

    /**
     * @see execute
     * @var Doctrine\DBAL\Driver\Statement
     */
    protected $insertMapEntryStatement = null;

    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);

        $this->databases = $this
            ->databaseProvider
            ->getMapsBySpecie($this->specie);

        // init insert statement
        $mapName = 'ProteinNameMap' . ucfirst($this->specie);
        $statement = "INSERT INTO " . $mapName .
        	" VALUES ('', ?, ?, ?, ?)";
        $this->insertMapEntryStatement = $this->connection->prepare($statement);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $recordsPerTransaction = 1000;

        $connection = $this->connection;

        $this->openConnection();

        foreach ($this->databases as $database) {
            $parserName = explode('\\', get_class($database));
            $parserName = array_pop($parserName);
            $output->writeln('  > loading map: ' . $parserName);

            $recordIdx = 0;
            $connection->beginTransaction();
            foreach ($database as $entry) {

                $this->insertMapEntryStatement->bindValue(1, $entry['namingConventionA']);
                $this->insertMapEntryStatement->bindValue(2, $entry['proteinNameA']);
                $this->insertMapEntryStatement->bindValue(3, $entry['namingConventionB']);
                $this->insertMapEntryStatement->bindValue(4, $entry['proteinNameB']);
                $this->insertMapEntryStatement->execute();

                $recordIdx++;
                if ($recordIdx == $recordsPerTransaction) {
                    $recordIdx = 0;

                    $connection->commit();
                    $connection->beginTransaction();

                    $output->writeln('  > ' . $recordsPerTransaction . ' records loaded');
                }
            }
            $connection->commit();
        }

        $this->closeConnection();
    }
}