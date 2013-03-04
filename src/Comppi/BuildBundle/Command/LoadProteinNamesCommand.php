<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadProteinNamesCommand extends AbstractLoadCommand
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    protected $connection;

    protected function configure() {
        $this
            ->setName('comppi:build:names')
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
        $output->writeln('  > loading names');

        $stmt = $this->connection->executeQuery(
        	'INSERT IGNORE INTO ProteinName(name)' .
			' SELECT name FROM NameToProtein;'
        );

        $output->writeln('  > ' . $stmt->rowCount() . ' names loaded');
    }
}