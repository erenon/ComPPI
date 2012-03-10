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
    
    /**
     * @TODO merge this and getMapsBySpecie
     */
    public function getInteractionsBySpecie($specie) {
        // get interaction database paths
        $interactionRoot = $this->rootDir . '/' . $specie . '/interaction/';
        $interactionFiles = new Finder();
        $interactionFiles->files()->in($interactionRoot);

        // available parsers
        $interactionParsers = $this->getInteractionParsers();
        
        // pass filenames to matching parsers
        $interactions = array();
        foreach ($interactionFiles as $interactionFileInfo) {
            $interactionFile = $interactionFileInfo->getPathname();
            foreach ($interactionParsers as $interactionParser) {
                if ($interactionParser::canParseFilename(basename($interactionFile))) {
                    $interactions[] = new $interactionParser($interactionFile);
                }
            }
        }
        
        return $interactions;        
    }
    
    private function getMapParsers() {
        $mapParsers = $this->getParsers(
            __DIR__ . '/Parser/Map', 
            __NAMESPACE__ . '\Parser\Map\\', 
            __NAMESPACE__ . '\Parser\Map\MapParserInterface'
        );
        
        return $mapParsers;
    }
    
    private function getInteractionParsers() {
        $interactionParsers = $this->getParsers(
            __DIR__ . '/Parser/Interaction', 
            __NAMESPACE__ . '\Parser\Interaction\\', 
            __NAMESPACE__ . '\Parser\Interaction\InteractionParserInterface'
        );
        
        return $interactionParsers;
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