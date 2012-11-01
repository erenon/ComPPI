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

    /**
     * @see execute
     * @var Doctrine\DBAL\Driver\Statement
     */
    protected $insertInteractionStatement = null;

    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);

        $this->databases = $this
            ->databaseProvider
            ->getInteractionsBySpecie($this->specie);

        // init insert statement
        $interactionEntityName = 'Interaction' . ucfirst($this->specie);
        $statement = "INSERT INTO " . $interactionEntityName .
        	" VALUES ('', ?, ?, ?, ?, ?, ?)";
        $this->insertInteractionStatement = $this->connection->prepare($statement);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
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

            // bind source name
            $this->insertInteractionStatement->bindValue(3, $sourceDb);

            // TODO setup isExperimental field
            $this->insertInteractionStatement->bindValue(6, false);

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

                $this->insertInteractionStatement->bindValue(1, $proteinAComppiId);
                $this->insertInteractionStatement->bindValue(2, $proteinBComppiId);
                $this->insertInteractionStatement->bindValue(4, $interaction['pubmedId']);
                $this->insertInteractionStatement->bindValue(5, $interaction['experimentalSystemType']);
                $this->insertInteractionStatement->execute();

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