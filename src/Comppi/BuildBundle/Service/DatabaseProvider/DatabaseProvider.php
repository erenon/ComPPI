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
        $mapParsers = $this->getParsers(
            __DIR__ . '/Parser/Map', 
            __NAMESPACE__ . '\Parser\Map\\', 
            __NAMESPACE__ . '\Parser\Map\MapParserInterface'
        );
        
        return $this->getParsersInstancesWithFiles($mapParsers, $mapFiles);
    }
    
    public function getInteractionsBySpecie($specie) {
        // get interaction database paths
        $interactionRoot = $this->rootDir . '/' . $specie . '/interaction/';
        $interactionFiles = new Finder();
        $interactionFiles->files()->in($interactionRoot);

        // available parsers
        $interactionParsers = $this->getParsers(
            __DIR__ . '/Parser/Interaction', 
            __NAMESPACE__ . '\Parser\Interaction\\', 
            __NAMESPACE__ . '\Parser\Interaction\InteractionParserInterface'
        );
        
        return $this->getParsersInstancesWithFiles($interactionParsers, $interactionFiles);        
    }
    
    public function getLocalizationsBySpecie($specie) {
        // get localization database paths
        $localizationRoot = $this->rootDir . '/' . $specie . '/localization/';
        $localizationFiles = new Finder();
        $localizationFiles->files()->in($localizationRoot);
        
        // available parsers
        $localizationParsers = $this->getParsers(
            __DIR__ . '/Parser/Localization', 
            __NAMESPACE__ . '\Parser\Localization\\', 
            __NAMESPACE__ . '\Parser\Localization\LocalizationParserInterface'
        );
        
        return $this->getParsersInstancesWithFiles($localizationParsers, $localizationFiles);
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
    
    
    private function getParsersInstancesWithFiles($parsers, $files) {
        // pass filenames to matching parsers
        $instances = array();
        foreach ($files as $file) {
            foreach ($parsers as $parser) {
                if ($parser::canParseFilename(basename($file))) {
                    $instances[] = new $parser($file);
                }
            }
        }
        
        return $instances;        
    }
}