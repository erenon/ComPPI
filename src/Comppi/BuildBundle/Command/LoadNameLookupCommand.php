<?php

namespace Comppi\BuildBundle\Command;

use Doctrine\DBAL\Types\IntegerType;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Types\IntegerType as IntegerParameter;

class LoadNameLookupCommand extends AbstractLoadCommand
{
    protected $commandName = 'namelookup';

    protected $usedEntities = array (
        'Protein' => 'READ',
        'ProteinNameMap' => 'READ',
        'NameToProtein' => 'WRITE'
    );

    /**
     * @see execute
     * @var Doctrine\DBAL\Driver\Statement
     */
    protected $selectProteins = null;

    /**
     * @see execute
     * @var Doctrine\DBAL\Driver\Statement
     */
    protected $insertSynonym = null;

    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);

        // @TODO this hack is required here because of a PDO bug
        // https://bugs.php.net/bug.php?id=44639
        $this->connection->getWrappedConnection()
             ->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('  > loading name lookup table for specie: ' . $this->specie->name);

        $connection = $this->connection;

        $statement = "SELECT id, proteinName, proteinNamingConvention FROM Protein" .
            " ORDER BY id ASC LIMIT ?, ?";

        $this->selectProteins = $connection->prepare($statement);

        $statement = "INSERT INTO NameToProtein" .
            " VALUES ('', ?, ?, ?, ?)";

        $this->insertSynonym = $connection->prepare($statement);

        $this->openConnection();

        // init params
        $proteinOffset = 0;
        $blockSize = 500;

        // execute protein select
        $this->selectProteins->bindValue(1, $proteinOffset, IntegerParameter::INTEGER);
        $this->selectProteins->bindValue(2, $blockSize, IntegerParameter::INTEGER);

        $this->selectProteins->execute();

        // while has rows
        while ($this->selectProteins->rowCount() > 0) {
            // iterate over rows
            $proteins = $this->selectProteins->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
            foreach ($proteins as $protein) {
                // reverse lookup name
                $synonyms = $this->proteinTranslator->getSynonyms(
                    $protein['proteinNamingConvention'],
                    $protein['proteinName'],
                    $this->specie->id
                );

                if (is_array($synonyms)) {
                    // insert name
                    $connection->beginTransaction();

                    foreach ($synonyms as $synonym) {
                        $this->insertSynonym->execute(array(
                            $this->specie->id,
                            $synonym['namingConventionA'],
                            $synonym['proteinNameA'],
                            $protein['id']
                        ));
                    }

                    $connection->commit();
                }
            }

            $output->writeln('  > ' . $this->selectProteins->rowCount() . ' proteins processed');

            // select proteins again
            $proteinOffset += $blockSize;

            $this->selectProteins->closeCursor();
            $this->selectProteins->bindValue(1, $proteinOffset, IntegerParameter::INTEGER);
            $this->selectProteins->execute();
        }

        $this->closeConnection();
    }
}