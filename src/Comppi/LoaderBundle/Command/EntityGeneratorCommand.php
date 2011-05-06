<?php

namespace Comppi\LoaderBundle\Command;


use Comppi\LoaderBundle\Service\EntityGenerator\EntityGenerator;

use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
//use Symfony\Component\Console\Input\InputArgument;
//use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;

class EntityGeneratorCommand extends Command
{
    private $parsers = array();
    private $databases;
    private $generator;
    
    public function __construct() {
        parent::__construct();
        $this->generator = new EntityGenerator();
    }
    
    protected function configure()
    {
        $this
            ->setName('comppi:load:entities')
            ->setDescription('Generates model entities from plaintext db headers')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->loadParsers(
            __DIR__ . '/../Service/EntityGenerator/Parser',
            'Comppi\\LoaderBundle\\Service\\EntityGenerator\\Parser\\'
        );   
        
        $this->loadDatabaseFiles(__DIR__ . '/../Resources/databases');
        
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
            __DIR__ . '/../Entity/' . ucfirst($name) . '.php',
            $this->generator->generate($name, $fields)
        );
    }
}
