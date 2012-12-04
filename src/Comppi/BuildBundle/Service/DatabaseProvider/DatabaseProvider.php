<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider;

use Symfony\Component\Finder\Finder;
use Comppi\BuildBundle\Service\SpecieProvider\SpecieDescriptor;

class DatabaseProvider
{
    private $rootDir;

    /**
     * Logger instance
     * @var Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    private $logger;
    private $mapParsers;

    public function __construct($databaseRootDir, $logger) {
        $this->rootDir = $databaseRootDir;
        $this->logger = $logger;
    }

    public function getMapsBySpecie(SpecieDescriptor $specie) {
        // get map database paths
        $mapDir = $this->rootDir . '/' . $specie->abbreviation . '/map/';
        $mapFiles = $this->getFilesInDir($mapDir);

        // available parsers
        $mapParsers = $this->getParsers(
            __DIR__ . '/Parser/Map',
            __NAMESPACE__ . '\Parser\Map\\',
            __NAMESPACE__ . '\Parser\Map\MapParserInterface'
        );

        return $this->getParsersInstancesWithFiles($mapParsers, $mapFiles);
    }

    public function getInteractionsBySpecie(SpecieDescriptor $specie) {
        // get interaction database paths
        $interactionDir = $this->rootDir . '/' . $specie->abbreviation . '/interaction/';
        $interactionFiles = $this->getFilesInDir($interactionDir);

        // available parsers
        $interactionParsers = $this->getParsers(
            __DIR__ . '/Parser/Interaction',
            __NAMESPACE__ . '\Parser\Interaction\\',
            __NAMESPACE__ . '\Parser\Interaction\InteractionParserInterface'
        );

        return $this->getParsersInstancesWithFiles($interactionParsers, $interactionFiles);
    }

    public function getLocalizationsBySpecie(SpecieDescriptor $specie) {
        // get localization database paths
        $localizationDir = $this->rootDir . '/' . $specie->abbreviation . '/localization/';
        $localizationFiles = $this->getFilesInDir($localizationDir);

        // available parsers
        $localizationParsers = $this->getParsers(
            __DIR__ . '/Parser/Localization',
            __NAMESPACE__ . '\Parser\Localization\\',
            __NAMESPACE__ . '\Parser\Localization\LocalizationParserInterface'
        );

        return $this->getParsersInstancesWithFiles($localizationParsers, $localizationFiles);
    }

    private function getFilesInDir($dir) {
        $finder = new Finder();
        try {
            $finder->files()->in($dir);
        } catch (\InvalidArgumentException $e) {
            $this->logger->notice('Source directory not found: ' . $dir);

            return array();
        }

        return $finder;
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
            $parserFound = false;

            foreach ($parsers as $parser) {
                if ($parser::canParseFilename(basename($file))) {
                    $instances[] = new $parser($file);
                    $parserFound = true;
                }
            }

            if ($parserFound === false) {
                $this->logger->notice('No parser found for source: \'' . $file . '\'');
            }
        }

        return $instances;
    }
}