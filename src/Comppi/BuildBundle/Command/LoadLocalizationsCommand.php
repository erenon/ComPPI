<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @TODO create AbstractLoadCommand for the sake of DRY
 */
class LoadLocalizationsCommand extends ContainerAwareCommand
{
    private $specie;
    private $databases;
    private $proteinTranslator;
    private $localizationTranslator;

    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;

    protected function configure() {
        $this
            ->setName('comppi:build:localizations')
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
        $this->databases = $databaseProvider->getLocalizationsBySpecie($specie);

        $this->proteinTranslator = $container->get('comppi.build.proteinTranslator');
        $this->localizationTranslator = $container->get('comppi.build.localizationTranslator');
        $this->connection = $this
            ->getContainer()
            ->get('doctrine.orm.default_entity_manager')
            ->getConnection();
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $localizationEntityName = 'ProteinToLocalization' . ucfirst($this->specie);
        $recordsPerTransaction = 500;

        $connection = $this->connection;

        foreach ($this->databases as $database) {
            $parserName = explode('\\', get_class($database));
            $parserName = array_pop($parserName);
            $output->writeln('  > loading localization database: ' . $parserName);

            $sourceDb = $database->getDatabaseIdentifier();

            $recordIdx = 0;
            $connection->beginTransaction();

            foreach ($database as $localization) {
                // get translated protein name
                $proteinComppiId = $this->proteinTranslator->getComppiId(
                    $localization['namingConvention'],
                    $localization['proteinId'],
                    $this->specie
                );

                try {
                    $localizationId = $this->localizationTranslator->getIdByLocalization($localization['localization']);
                } catch (\InvalidArgumentException $e) {
                    $output->writeln('  - '. $localization['localization'] . ' not found in localization tree');
                    continue;
                }

                $this->addDatabaseRefToId($sourceDb, $proteinComppiId, $this->specie);

                $connection->insert($localizationEntityName, array(
                    'proteinId' => $proteinComppiId,
                    'localizationId' => $localizationId,
                    'sourceDb' => $sourceDb,
                    'pubmedId' => $localization['pubmedId'],
                    'experimentalSystemType' => $localization['experimentalSystemType']
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