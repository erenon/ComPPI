<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Component\Validator\Constraints\Valid;

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
        'ProteinNameMap' => 'READ',
        'ProtLocToSystemType' => 'WRITE',
        'SystemType' => 'WRITE'
    );

    /**
     * @see execute
     * @var Doctrine\DBAL\Driver\Statement
     */
    protected $insertLocalizationStatement = null;

    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);

        $this->databases = $this
            ->databaseProvider
            ->getLocalizationsBySpecie($this->specie);

        // init insert statement
        $this->insertLocalizationStatement = $this->connection->prepare(
            'INSERT INTO ProteinToLocalization VALUES (NULL, ?, ?, ?, ?)' .
            ' ON DUPLICATE KEY UPDATE id=id'
        );

        // init add system type statement
        $this->addSystemTypeStatement = $this->connection->prepare(
            'INSERT INTO ProtLocToSystemType VALUES (?, ?)' .
        	' ON DUPLICATE KEY UPDATE protLocId = protLocId'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $recordsPerTransaction = 500;

        $connection = $this->connection;
        $lastInsertId = 0;

        $this->openConnection();

        foreach ($this->databases as $database) {
            $parserName = explode('\\', get_class($database));
            $parserName = array_pop($parserName);
            $output->writeln('  > loading localization database: ' . $parserName);

            $sourceDb = $database->getDatabaseIdentifier();

            $recordIdx = 0;
            $connection->beginTransaction();

            // bind source name
            $this->insertLocalizationStatement->bindValue(3, $sourceDb);

            foreach ($database as $localization) {
                // get localizationId
                try {
                    $localizationId = $this->localizationTranslator->getIdByLocalization($localization['localization']);
                } catch (\InvalidArgumentException $e) {
                    $output->writeln('  - '. $localization['localization'] . ' not found in localization tree');
                    continue;
                }

                // get translated protein names
                $proteinComppiIds = $this->proteinTranslator->getComppiIds(
                    $localization['namingConvention'],
                    $localization['proteinId'],
                    $this->specie->id
                );

                foreach ($proteinComppiIds as $proteinComppiId) {
                    $this->addDatabaseRefToId($sourceDb, $proteinComppiId);

                    $this->insertLocalizationStatement->bindValue(1, $proteinComppiId);
                    $this->insertLocalizationStatement->bindValue(2, $localizationId);
                    $this->insertLocalizationStatement->bindValue(4, $localization['pubmedId']);
                    $this->insertLocalizationStatement->execute();

                    $id = $this->connection->lastInsertId();

                    if ($id !== intval($lastInsertId) && $id != 0) {
                        $lastInsertId = $id;

                        $this->addSystemTypes($id, $localization['experimentalSystemType']);

                        // flush transaction
                        $recordIdx++;
                        if ($recordIdx % $recordsPerTransaction == 0) { // flush transaction
                            $connection->commit();
                            $connection->beginTransaction();

                            if ($recordIdx % 5000 == 0) {
	                            $output->writeln('  > 5000 records loaded');
                            }
                        }
                    } // else ON DUPLICATE KEY => update
                }
            }

            $connection->commit();
            
            // stats
            $output->writeln("  = " . $recordIdx . ' records loaded (unfiltered total: ' .
                $database->getUnfilteredEntryCount() . ')');
        }

        $this->closeConnection();
    }
}