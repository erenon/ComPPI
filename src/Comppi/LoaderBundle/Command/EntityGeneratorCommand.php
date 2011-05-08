<?php

namespace Comppi\LoaderBundle\Command;


use Comppi\LoaderBundle\Service\EntityGenerator\EntityGenerator;

//use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
//use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;

class EntityGeneratorCommand extends Command
{
    private $container;
    private $generator;
    private $parsers = array();
    private $databases;
    
    private $parser_dir;
    private $parser_namespace;
    private $database_dir;
    private $output_dir;
    
    protected function configure()
    {
        $this
            ->setName('comppi:load:entities')
            ->setDescription('Generates model entities from plaintext db headers')
            ->setHelp('All option paths are relative to the LoaderBundle')
            ->addOption('parser_dir', null, InputArgument::OPTIONAL, 'Path to the plaintext database header parsers')
            ->addOption('parser_namespace', null, InputArgument::OPTIONAL, 'Namespace of header parsers')
            ->addOption('database_dir', null, InputArgument::OPTIONAL, 'Path to the plaintext databases')
            ->addOption('output_dir', null, InputArgument::OPTIONAL, 'Path to the dir of generated entities')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        //parse databases
        foreach ($this->databases as $database) {
            //look for matching parser
            foreach ($this->parsers as $parser) {
                $filename = basename($database);
                if ($parser->isMatch($filename)) {
                    //parser found, open database file
                    $handle = fopen($database, "r");
                    if ($handle) {
                        $fields = $parser->getFieldArray($handle);
                        fclose($handle);
                        $this->generateEntity($filename, $fields);
                    } else {
                        throw new \Exception("Can't open file '" . $database . "'");
                    }
                    break;
                }
            }
        }
        
    }
    
    protected function initialize(InputInterface $input, OutputInterface $output) {
        $this->container = $this->getApplication()->getKernel()->getContainer();
        $this->generator = $this->container->get('loader.entity_generator');
        
        $this->loadOptions($input);        
        
        $this->loadParsers(
            __DIR__ . '/../' . $this->parser_dir,
            $this->parser_namespace
        );   
        
        $this->loadDatabaseFiles(__DIR__ . '/../' . $this->database_dir);
    }
    
    private function loadOptions(InputInterface $input) {
        
        $keys = array(
            'parser_dir',
            'parser_namespace',
            'database_dir',
            'output_dir'
        );
        
        foreach ($keys as $key) {
            if (!$value = $input->getOption($key)) {
                $value = $this->container->getParameter('loader.' . $key);
            }
            
            $this->$key = $value;
        }
    }
    
    private function loadParsers($parser_dir, $parser_namespace) {
        $parsers = new Finder();
        $parsers->files()->name('*.php')->in($parser_dir);
        
        foreach ($parsers as $parser) {
            $classname = $parser_namespace . basename($parser->getRealpath(), '.php');
            
            if (class_exists($classname)) {
                $reflection = new \ReflectionClass($classname);
                if ($reflection->isInstantiable() && $reflection->implementsInterface($parser_namespace . 'ParserInterface')) {
                    $this->parsers[] = new $classname;
                }
            }
        }
    }
    
    private function loadDatabaseFiles($database_dir) {
        $dbs = new Finder();
        $dbs->files()->in($database_dir);
        
        $this->databases = $dbs;     
    }
    
    private function generateEntity($name, array $fields) {
        file_put_contents(
            __DIR__ . '/../' . $this->output_dir . '/' . ucfirst($name) . '.php',
            $this->generator->generate($name, $fields)
        );
    }
}
