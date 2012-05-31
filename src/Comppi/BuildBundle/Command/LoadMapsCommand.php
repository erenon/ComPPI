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

    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);

        $this->databases = $this
            ->databaseProvider
            ->getMapsBySpecie($this->specie);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $entityName = 'ProteinNameMap' . ucfirst($this->specie);

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

                $connection->insert($entityName, $entry);

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