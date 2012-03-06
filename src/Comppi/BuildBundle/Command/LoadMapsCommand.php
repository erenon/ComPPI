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

    protected function configure() {
        $this
            ->setName('comppi:build:maps')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();

        $databaseProvider = $container->get('comppi.build.databaseProvider');
        $this->maps = $databaseProvider->getMaps();

        $this->mapLoader = $container->get('comppi.build.mapLoader');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        foreach ($this->maps as $map) {
            $this->mapLoader->loadMap($map);
        }
    }
}