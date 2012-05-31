<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadLocalizationsCommand extends AbstractLoadCommand
{
    protected $commandName = 'localizations';

    protected $usedEntities = array(
        'ProteinToLocalization' => 'WRITE',
        'Protein' => 'WRITE',
        'ProteinToDatabase' => 'WRITE',
        'ProteinNameMap' => 'READ'
    );

    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);

        $this->databases = $this
            ->databaseProvider
            ->getLocalizationsBySpecie($this->specie);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $localizationEntityName = 'ProteinToLocalization' . ucfirst($this->specie);
        $recordsPerTransaction = 500;

        $connection = $this->connection;

        $this->openConnection();

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

        $this->closeConnection();
    }
}