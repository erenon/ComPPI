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
    private $mapLoader;
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

        $this->mapLoader = $container->get('comppi.build.mapLoader');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        foreach ($this->maps as $map) {
            $this->mapLoader->loadMap($map, $this->specie);
        }
    }
}