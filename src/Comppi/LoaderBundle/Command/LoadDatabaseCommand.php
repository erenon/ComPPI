<?php

namespace Comppi\LoaderBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadDatabaseCommand extends Command
{
    private $container;
    private $databases;
    private $parser;
    private $entity_manager;
    
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
        $this->entity_manager = $this->container->get('doctrine.orm.default_entity_manager');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) { 
        foreach ($this->databases as $database) {
            $entity_name = $this->parser->getEntityName($database);
            $entity_full_name = 'Comppi\\LoaderBundle\\Entity\\' . $entity_name;

            $field_names = $this->parser->getFieldArray($database);
            $records = $this->parser->getContentArray($database);
            
            foreach ($records as $record) {
                reset($field_names);
                $entity = new $entity_full_name();
                foreach ($record as $field_value) {
                    $method = 'set' . ucfirst(current($field_names));
                    call_user_func(
                        array(
                            $entity,
                    		$method
                        ),
                        $field_value
                    );
                    
                    next($field_names);
                }
                
                $this->entity_manager->persist($entity);
            }
            $this->entity_manager->flush();
            $output->writeln("  > Entity " . $entity_name . ' loaded');
            $output->writeln('    ' . join(', ', $field_names));
        }
    }  
}