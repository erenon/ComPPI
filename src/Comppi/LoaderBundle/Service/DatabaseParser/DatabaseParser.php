<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser;

use Symfony\Component\Finder\Finder;

class DatabaseParser
{
    private $parsers;
    
    public function __construct($parser_dir, $parser_namespace) {
        $this->loadParsers($parser_dir, $parser_namespace);
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

    private function getMatchingParser($database_name) {
        foreach ($this->parsers as $parser) {
            if ($parser->isMatch($database_name)) {
                return $parser;
            }
        }
        
        return false;
    }
    
    public function getFieldArray($database_path) {
        $filename = basename($database_path);
        $parser = $this->getMatchingParser($filename);

        if ($parser) {
            //matching parser found
            $handle = fopen($database_path, "r");
            if ($handle) {
                $fields = $parser->getFieldArray($handle);
                fclose($handle);
                
                return $fields;
                
            } else {
                throw new \Exception("Can't open file '" . $database_path . "'");
            }
            
        } else {
            throw new \UnexpectedValueException("No parser found for database: '" . $database_path . "'");
        }        
    }
}