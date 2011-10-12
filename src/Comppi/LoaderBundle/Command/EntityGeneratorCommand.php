<?php

namespace Comppi\LoaderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EntityGeneratorCommand extends ContainerAwareCommand
{ 
    private $generator;
    private $databases;
    private $parser;

    private $output_dir;    
    
    protected function configure() {
        $this
            ->setName('comppi:load:entities')
            ->setDescription('Generates model entities from plaintext-db headers')
            ->setHelp('All option paths are relative to the LoaderBundle')
            ->addOption('output_dir', null, InputArgument::OPTIONAL, 'Path to the dir of generated entities')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        //parse databases
        foreach ($this->databases as $database) {
            try {
                $entity_name = $this->parser->getEntityName($database);
                $fields = $this->parser->getFieldArray($database);
                $field_types = $this->parser->getFieldTypeArray($database);
                $this->generateEntity($entity_name, $fields, $field_types);
                
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }
    
    protected function initialize(InputInterface $input, OutputInterface $output) {
        $this->loadOptions($input);
            
        $container = $this->getContainer();
        $this->generator = $container->get('comppi.loader.entity_generator');
        $this->parser = $container->get('comppi.loader.database_parser');
        $this->databases = $container->get('comppi.loader.databases')->getFilePaths();
        
        //init output dir
        $this->output_dir = __DIR__ . '/../' . $this->output_dir;
        if (!is_dir($this->output_dir)) {
            mkdir($this->output_dir);
        }
    }
    
    private function loadOptions(InputInterface $input) {
        /** @todo simplify this if no more options arise */
        $keys = array(
            'output_dir'
        );
        
        foreach ($keys as $key) {
            if (!$value = $input->getOption($key)) {
                // use default value
                $value = $this->getContainer()->getParameter('comppi.loader.entity_generator_command.' . $key);
            }
            
            $this->$key = $value;
        }
    }
    
    private function generateEntity($name, array $fields, array $types) {
        $filename = $this->output_dir . '/' . ucfirst($name) . '.php';
        
        file_put_contents(
            $filename,
            $this->generator->generate($name, $fields, $types)
        );
    }
}