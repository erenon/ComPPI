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
        'ProteinNameMap' => 'READ',
        'InteractionToSystemType' => 'WRITE',
        'SystemType' => 'WRITE'
    );

    /**
     * @see execute
     * @var Doctrine\DBAL\Driver\Statement
     */
    protected $insertInteractionStatement;

    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);

        $this->databases = $this
            ->databaseProvider
            ->getInteractionsBySpecie($this->specie);

        // init insert statement
        $this->insertInteractionStatement = $this->connection->prepare(
        	"INSERT INTO Interaction VALUES ('', ?, ?, ?, ?)"
        );

        // init add system type statement
        $this->addSystemTypeStatement = $this->connection->prepare(
            'INSERT INTO InteractionToSystemType VALUES (?, ?)'
        );
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

            foreach ($database as $interaction) {
                // get proteinA name
                $proteinAOriginalName = $interaction['proteinAName'];
                $proteinANamingConvention = $interaction['proteinANamingConvention'];
                $proteinAComppiId = $translator->getComppiId(
                    $proteinANamingConvention, $proteinAOriginalName, $this->specie->id
                );

                // get proteinB name
                $proteinBOriginalName = $interaction['proteinBName'];
                $proteinBNamingConvention = $interaction['proteinBNamingConvention'];
                $proteinBComppiId = $translator->getComppiId(
                    $proteinBNamingConvention, $proteinBOriginalName, $this->specie->id
                );

                $this->addDatabaseRefToId($sourceDb, $proteinAComppiId);
                $this->addDatabaseRefToId($sourceDb, $proteinBComppiId);

                $this->insertInteractionStatement->bindValue(1, $proteinAComppiId);
                $this->insertInteractionStatement->bindValue(2, $proteinBComppiId);
                $this->insertInteractionStatement->bindValue(4, $interaction['pubmedId']);
                $this->insertInteractionStatement->execute();

                $id = $this->connection->lastInsertId();

                $this->addSystemTypes($id, $interaction['experimentalSystemType']);

                $recordIdx++;
                if ($recordIdx == $recordsPerTransaction) { // flush transaction
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