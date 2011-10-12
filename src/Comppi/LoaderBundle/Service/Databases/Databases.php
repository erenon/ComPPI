<?php

namespace Comppi\LoaderBundle\Service\Databases;

use Symfony\Component\Finder\Finder;

class Databases
{
    private $database_dir;
    private $databases = null;
    
    public function __construct($database_dir) {
        $this->database_dir = $database_dir;    
    }
    
    public function getFilePaths() {
        if ($this->databases == null) {
            $dbs = new Finder();
            $dbs->files()->in($this->database_dir);
            $this->databases = $dbs; 
        }
        
        return $this->databases;
    }
}