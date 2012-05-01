<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadMapsCommand extends ContainerAwareCommand
{
    private $maps;
    private $specie;

    protected function configure() {
        $this
            ->setName('comppi:build:maps')
            ->addArgument('specie', InputArgument::REQUIRED, 'Specie abbreviation to load')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();

        $databaseProvider = $container->get('comppi.build.databaseProvider');

        $specie = $input->getArgument('specie');
        if (!$specie) {
            throw new \Exception("Please specify a specie");
        }
        $this->specie = $specie;
        //$this->maps = $databaseProvider->getMaps();
        $this->maps = $databaseProvider->getMapsBySpecie($specie);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $entityName = 'ProteinNameMap' . ucfirst($this->specie);

        $recordsPerTransaction = 1000;

        $connection = $this
            ->getContainer()
            ->get('doctrine.orm.default_entity_manager')
            ->getConnection();

        // avoid memory leak
        $connection->getConfiguration()->setSQLLogger(null);

        foreach ($this->maps as $map) {
            $output->writeln('  > loading map: ' . get_class($map));
            $recordIdx = 0;
            $connection->beginTransaction();
            foreach ($map as $entry) {

                $connection->insert($entityName, $entry);

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
}