<?php

namespace Comppi\LoaderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadDatabaseCommand extends ContainerAwareCommand
{ 
    private $databases;
    private $parser;
    private $entity_manager;
    
    protected function configure() {
        $this
            ->setName('comppi:load:database')
            ->setDescription('Loads plaintext databases into the configured database')
        ;
    }
    
    protected function initialize(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();
        $this->databases = $container->get('comppi.loader.databases')->getFilePaths();
        $this->parser = $container->get('comppi.loader.database_parser');
        $this->entity_manager = $container->get('doctrine.orm.default_entity_manager');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        $connection = $this->entity_manager->getConnection();
        
        foreach ($this->databases as $database) {
            $entity_name = $this->parser->getEntityName($database);

            $field_names = $this->parser->getFieldArray($database);
            $iterator = $this->parser->getRecordIterator($database);
            
            $record_count_per_transaction = 1000;
            $record_index = 0;
            
            $connection->beginTransaction();
            foreach ($iterator as $record) {
                $data = array();
                reset($field_names);
                
                foreach ($record as $field_value) {
                    $data[current($field_names)] = $field_value;
                    
                    next($field_names);
                }                
                
                // TODO may use multiinsert here
                $connection->insert($entity_name, $data);
                
                $record_index++;
                if ($record_index % $record_count_per_transaction == 0) {
                    $connection->commit();
                    $connection->beginTransaction();
                    
                    if ($record_index % ($record_count_per_transaction*2) == 0) {
                        $sign = '  / ';
                    } else {
                        $sign = '  \ ';
                    }
                    $output->writeln($sign . $record_count_per_transaction . ' records loaded');
                }
            }
            $connection->commit();
            
            $output->writeln("  > Entity " . $entity_name . ' loaded (' . $record_index . ' records)');
        }
    } 
}