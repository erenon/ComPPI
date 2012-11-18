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
        $localizationEntityName = 'ProteinToLocalization' . ucfirst($this->specie);
        $statement = "INSERT INTO " . $localizationEntityName .
        	" VALUES ('', ?, ?, ?, ?, ?, ?)";
        $this->insertLocalizationStatement = $this->connection->prepare($statement);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
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

            // bind source name
            $this->insertLocalizationStatement->bindValue(3, $sourceDb);

            // TODO setup isExperimental field
            $this->insertLocalizationStatement->bindValue(6, false);

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

                $this->insertLocalizationStatement->bindValue(1, $proteinComppiId);
                $this->insertLocalizationStatement->bindValue(2, $localizationId);
                $this->insertLocalizationStatement->bindValue(4, $localization['pubmedId']);
                $this->insertLocalizationStatement->bindValue(5, $localization['experimentalSystemType']);
                $this->insertLocalizationStatement->execute();

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