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

    private function getMatchingParser($filename) {
        foreach ($this->parsers as $parser) {
            if ($parser->isMatch($filename)) {
                return $parser;
            }
        }
        
        return false;
    }
    
    private function callParserFunc($database_path, $func) {
        $filename = basename($database_path);
        $parser = $this->getMatchingParser($filename);

        if ($parser) {
            //matching parser found
            $handle = fopen($database_path, "r");
            if ($handle) {
                $return = call_user_func(
                    array($parser, $func),
                    $handle
                );
                 
                fclose($handle);
                
                return $return;
                
            } else {
                throw new \Exception("Can't open file '" . $database_path . "'");
            }
            
        } else {
            throw new \UnexpectedValueException("No parser found for database: '" . $database_path . "'");
        }        
    }
    
    public function getFieldArray($database_path) {
        return $this->callParserFunc($database_path, 'getFieldArray');       
    }
    
    public function getFieldTypeArray($database_path) {
        return $this->callParserFunc($database_path, 'getFieldTypeArray');
    }
    
    public function getContentArray($database_path) {
        try {
            return $this->callParserFunc($database_path, 'getContentArray');
        } catch (\Exception $e) {
            throw new \Exception("Failed to parse database: '" . $database_path . "'", 0, $e);
        } 
    }
    
    public function getEntityName($database_path) {
        $filename = basename($database_path);
        $parser = $this->getMatchingParser($filename);
        
        if ($parser) { 
            return $parser->getEntityName($filename);
        } else {
            throw new \UnexpectedValueException("No parser found for database: '" . $database_path . "'");
        }
    }
}