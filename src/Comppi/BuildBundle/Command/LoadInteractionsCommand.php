<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadInteractionsCommand extends ContainerAwareCommand
{
    /**
     * Collection of interaction databases
     */
    private $databases;
    private $translator;
    private $specie;

    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;

    protected function configure() {
        $this
            ->setName('comppi:build:interactions')
            ->addArgument('specie', InputArgument::REQUIRED, 'Specie abbreviation to load')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();

        $specie = $input->getArgument('specie');
        if (!$specie) {
            throw new \Exception("Please specify a specie");
        }
        $this->specie = $specie;

        $databaseProvider = $container->get('comppi.build.databaseProvider');
        $this->databases = $databaseProvider->getInteractionsBySpecie($specie);

        $this->translator = $container->get('comppi.build.proteinTranslator');
        $this->connection = $this
            ->getContainer()
            ->get('doctrine.orm.default_entity_manager')
            ->getConnection();
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $interactionEntityName = 'Interaction' . ucfirst($this->specie);
        $recordsPerTransaction = 500;

        $connection = $this->connection;
        $translator = $this->translator;

        // avoid memory leak
        $connection->getConfiguration()->setSQLLogger(null);

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
    }

    /**
     * @TODO This method uses a mysql specific ON DUPLICATE KEY UPDATE clause
     * This could be substituted with a select and a conditional insert
     *
     * @param string $sourceDb
     * @param string $comppiId
     * @param string $specie
     */
    private function addDatabaseRefToId($sourceDb, $comppiId, $specie) {
        $proteinToDatabaseTable = 'ProteinToDatabase' . ucfirst($specie);

        // insert ref only if not yet inserted
        $this->connection->executeQuery(
            'INSERT INTO ' .$proteinToDatabaseTable.
            ' VALUES (?, ?)'.
            ' ON DUPLICATE KEY UPDATE proteinId=proteinId',
            array($comppiId, $sourceDb)
        );
    }
}