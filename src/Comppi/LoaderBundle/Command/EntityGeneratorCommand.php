<?php

namespace Comppi\LoaderBundle\Command;

use Comppi\LoaderBundle\Service\EntityGenerator\EntityGenerator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;

class EntityGeneratorCommand extends Command
{
    private $container;
    private $generator;
    private $databases;
    private $parser;

    private $output_dir;
    
    protected function configure()
    {
        $this
            ->setName('comppi:load:entities')
            ->setDescription('Generates model entities from plaintext db headers')
            ->setHelp('All option paths are relative to the LoaderBundle')
            ->addOption('output_dir', null, InputArgument::OPTIONAL, 'Path to the dir of generated entities')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        //parse databases
        foreach ($this->databases as $database) {
            try {
                $filename = basename($database);
                $fields = $this->parser->getFieldArray($database);
                $this->generateEntity($filename, $fields);
                
            } catch (Exception $e) {
                throw $e;
            }
        }
        
    }
    
    protected function initialize(InputInterface $input, OutputInterface $output) {
        $this->container = $this->getApplication()->getKernel()->getContainer();
        $this->generator = $this->container->get('loader.entity_generator');
        $this->parser = $this->container->get('loader.database_parser');
        $this->databases = $this->container->get('loader.databases')->getFilePaths();
        
        $this->loadOptions($input);        
    }
    
    private function loadOptions(InputInterface $input) {
        /** @todo simplify this if no more options arises */
        $keys = array(
            'output_dir'
        );
        
        foreach ($keys as $key) {
            if (!$value = $input->getOption($key)) {
                $value = $this->container->getParameter('loader.entity_generator_command.' . $key);
            }
            
            $this->$key = $value;
        }
    }
    
    private function generateEntity($name, array $fields) {
        file_put_contents(
            __DIR__ . '/../' . $this->output_dir . '/' . ucfirst($name) . '.php',
            $this->generator->generate($name, $fields)
        );
    }
}
