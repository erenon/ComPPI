<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider;

use Symfony\Component\Finder\Finder;

class DatabaseProvider
{
    private $rootDir;
    private $mapParsers;

    public function __construct($databaseRootDir) {
        $this->rootDir = $databaseRootDir;
    }

    public function getMapsBySpecie($specie) {
        // get map database paths
        $mapRoot = $this->rootDir . '/' . $specie . '/map/';
        $mapFiles = new Finder();
        $mapFiles->files()->in($mapRoot);

        // available parsers
        $mapParsers = $this->getMapParsers();
        
        // pass filenames to matching parsers
        $maps = array();
        foreach ($mapFiles as $mapFile) {
            foreach ($mapParsers as $mapParser) {
                if ($mapParser::canParseFilename(basename($mapFile))) {
                    $maps[] = new $mapParser($mapFile);
                }
            }
        }
        
        return $maps;
    }
    
    private function getMapParsers() {
        $mapParsers = $this->getParsers(
            __DIR__ . '/Parser/Map', 
            __NAMESPACE__ . '\Parser\Map\\', 
            __NAMESPACE__ . '\Parser\Map\MapParserInterface'
        );
        
        return $mapParsers;
    }
    
    private function getParsers($parserDir, $parserNamespace, $parserInterface) {
        $parsers = array();
        $parserFiles = new Finder();
        $parserFiles->files()->name('*.php')->in($parserDir);
        
        foreach ($parserFiles as $parserFile) {
            $classname = $parserNamespace . basename($parserFile->getRealpath(), '.php');
            
            if (class_exists($classname)) {
                $reflection = new \ReflectionClass($classname);
                
                if ($reflection->isInstantiable()
                &&  $reflection->implementsInterface($parserInterface)) {
                    $parsers[] = $classname;
                }
            }
        }
        
        return $parsers;
    }
}