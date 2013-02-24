<?php

namespace Comppi\BuildBundle\Test\Service\DatabaseProvider\Parser\Map;

use Comppi\BuildBundle\Test\Common\KernelAwareTest;

class GenericMapTest extends KernelAwareTest
{
    /**
     * @var Comppi\BuildBundle\Service\DatabaseProvider\DatabaseProvider
     */
    protected $databaseProvider;

    /**
     * @var Comppi\BuildBundle\Service\SpecieProvider\SpecieProvider
     */
    protected $specieProvider;

    public function setUp() {
        parent::setUp();

        $this->databaseProvider = $this->container->get('comppi.build.databaseProvider');
        $this->specieProvider = $this->container->get('comppi.build.specieProvider');
    }

    public function testOutput() {
        $expectedPath = $_ENV['build_test_database_expected_path'];
        $species = $this->specieProvider->getDescriptors();
        $missingExpectations = array();

        foreach ($species as $specie) {
            $parsers = $this->databaseProvider->getMapsBySpecie($specie);

            $expectedPathSpecie = $expectedPath .
                    DIRECTORY_SEPARATOR .
                    $specie->abbreviation .
                    DIRECTORY_SEPARATOR .
                    'map'.
                    DIRECTORY_SEPARATOR;

            foreach ($parsers as $parser) {
                $fileNameInfo = $parser->getFileInfo();
                $fileNameBase = $fileNameInfo->getFilename();

                $expectedFile = $expectedPathSpecie . $fileNameBase;

                if (is_file($expectedFile)) {
                    $this->compareParserOutputToFile($parser, $expectedFile);
                } else {
                    $missingExpectations[] = $fileNameBase;
                }
            }
        }

        if (empty($missingExpectations) === false) {
            $missingList = join(",\n - ", $missingExpectations);

            $this->markTestIncomplete(
                "Expectation files are missing for the following inputs:\n - " .
                $missingList
            );
        }
    }

    private function compareParserOutputToFile($parser, $file) {
        // echo 'Compare ' . $parser->getDatabaseIdentifier() . ' to file ' . $file . "\n";

        $expected = array();

        if (($handle = fopen($file, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                $expected[] = $data;
            }
            fclose($handle);
        } else {
            $this->fail('Failed to open expectation file: ' . $file);
        }

        $actual = array();

        foreach ($parser as $record) {
            // Uncomment to get expected file:
            // echo join(';', $record) . "\n";

            $actual[] = array(
                $record['namingConventionA'],
                $record['namingConventionB'],
                $record['proteinNameA'],
                $record['proteinNameB']
            );
        }

        $this->assertEquals($expected, $actual);
    }
}