<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadNameCleanCommand extends AbstractLoadCommand
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    protected $connection;

    protected function configure() {
        $this
            ->setName('comppi:build:nameClean')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();

        // setup database connection
        $this->connection = $container
            ->get('doctrine.orm.default_entity_manager')
            ->getConnection();

        // avoid memory leak
        $this->connection->getConfiguration()->setSQLLogger(null);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $connection = $this->connection;

        $output->writeln('  > add foreign keys');

        $fkStatements = array(
            "ALTER TABLE ProteinToLocalization ADD CONSTRAINT protloc_to_protein FOREIGN KEY (proteinId) REFERENCES Protein (id) ON DELETE Cascade;",
            "ALTER TABLE ProteinToDatabase ADD CONSTRAINT db_to_protein FOREIGN KEY (proteinId) REFERENCES Protein (id) ON DELETE Cascade;",
            "ALTER TABLE ProtLocToSystemType ADD CONSTRAINT protloc_to_loc FOREIGN KEY (protLocId) REFERENCES ProteinToLocalization (id) ON DELETE Cascade;",
            "ALTER TABLE ProtLocToSystemType ADD CONSTRAINT protloc_to_systype FOREIGN KEY (systemTypeId) REFERENCES SystemType (id) ON DELETE Cascade;",
            "ALTER TABLE ConfidenceScore ADD CONSTRAINT confidence_to_interaction FOREIGN KEY (interactionId) REFERENCES Interaction (id) ON DELETE Cascade;",
            "ALTER TABLE Interaction ADD CONSTRAINT interaction_a_to_protein FOREIGN KEY (actorAId) REFERENCES Protein (id) ON DELETE Cascade;",
            "ALTER TABLE Interaction ADD CONSTRAINT interaction_b_to_protein FOREIGN KEY (actorBId) REFERENCES Protein (id) ON DELETE Cascade;",
            "ALTER TABLE InteractionToSystemType ADD CONSTRAINT intsystype_to_interaction FOREIGN KEY (interactionId) REFERENCES Interaction (id) ON DELETE Cascade;",
            "ALTER TABLE InteractionToSystemType ADD CONSTRAINT intsystype_to_systype FOREIGN KEY (systemTypeId) REFERENCES SystemType (id) ON DELETE Cascade;",
            "ALTER TABLE NameToProtein ADD CONSTRAINT name_to_protein FOREIGN KEY (proteinId) REFERENCES Protein (id) ON DELETE Cascade;"
        );

        foreach ($fkStatements as $fkStatement) {
            $connection->executeQuery($fkStatement);
        }

        $output->writeln('  > clean names');

        $stmt = $connection->executeQuery(
        	"DELETE FROM Protein" .
        	" WHERE proteinNamingConvention" .
        	" NOT IN ('UniProtKB/TrEmbl', 'UniProtKB/Swiss-Prot')"
        );

        $output->writeln('  > ' . $stmt->rowCount() . ' records removed');
    }
}