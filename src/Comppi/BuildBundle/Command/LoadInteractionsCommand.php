<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadInteractionsCommand extends AbstractLoadCommand
{
    protected $commandName = 'interactions';

    protected $usedEntities = array(
        'Interaction' => 'WRITE',
        'Protein' => 'WRITE',
        'ProteinToDatabase' => 'WRITE',
        'ProteinNameMap' => 'READ'
    );

    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);

        $this->databases = $this
            ->databaseProvider
            ->getInteractionsBySpecie($this->specie);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $interactionEntityName = 'Interaction' . ucfirst($this->specie);
        $recordsPerTransaction = 500;

        $connection = $this->connection;
        $translator = $this->proteinTranslator;

        $this->openConnection();

        foreach ($this->databases as $database) {
            $parserName = explode('\\', get_class($database));
            $parserName = array_pop($parserName);
            $output->writeln('  > loading interaction database: ' . $parserName);

            $sourceDb = $database->getDatabaseIdentifier();

            $recordIdx = 0;
            $connection->beginTransaction();

            foreach ($database as $interaction) {
                // get proteinA name
                $proteinAOriginalName = $interaction['proteinAName'];
                $proteinANamingConvention = $interaction['proteinANamingConvention'];
                $proteinAComppiId = $translator->getComppiId(
                    $proteinANamingConvention, $proteinAOriginalName, $this->specie
                );

                // get proteinB name
                $proteinBOriginalName = $interaction['proteinBName'];
                $proteinBNamingConvention = $interaction['proteinBNamingConvention'];
                $proteinBComppiId = $translator->getComppiId(
                    $proteinBNamingConvention, $proteinBOriginalName, $this->specie
                );

                $this->addDatabaseRefToId($sourceDb, $proteinAComppiId, $this->specie);
                $this->addDatabaseRefToId($sourceDb, $proteinBComppiId, $this->specie);

                $connection->insert($interactionEntityName, array(
                    'actorAId' => $proteinAComppiId,
                    'actorBId' => $proteinBComppiId,
                    'sourceDb' => $sourceDb,
                    'pubmedId' => $interaction['pubmedId'],
                    'experimentalSystemType' => $interaction['experimentalSystemType']
                ));

                // flush transaction
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