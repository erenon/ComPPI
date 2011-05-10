<?php

namespace Comppi\LoaderBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
/*use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;*/

use ComppiComppi\LoaderBundle\Entity;

class LoadDatabaseCommand extends Command
{
    private $container;
    private $databases;
    private $parser;
    
    protected function configure()
    {
        $this
            ->setName('comppi:load:database')
            ->setDescription('Loads plaintext databases into the configured database')
        ;
    }
    
    protected function initialize(InputInterface $input, OutputInterface $output) {
        $this->container = $this->getApplication()->getKernel()->getContainer();
        $this->databases = $this->container->get('loader.databases')->getFilePaths();
        $this->parser = $this->container->get('loader.database_parser');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) { 
        foreach ($this->databases as $database) {
            $max = 0;
            $max_name = "";
            $records = $this->parser->getContentArray($database);
            foreach ($records as $record) {
                foreach ($record as $field) {
                    if (strlen($field) > $max) {
                        $max = strlen($field);
                        $max_name = basename($database) . " / " . $field;
                    }
                }
            }
            
            $output->writeln($max . ' : ' . $max_name);
        }
    }  
}