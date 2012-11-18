<?php

namespace Comppi\BuildBundle\Test\Service\DatabaseProvider\Parser\Interaction;

use Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction\ConsensusPathDb;

class ConsensusPathDbTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction\ConsensusPathDb
     */
    protected $parser;

    protected $expectedPath;

    protected function setUp() {
        $commonPath = DIRECTORY_SEPARATOR .
            'hs' . DIRECTORY_SEPARATOR .
            'interaction' . DIRECTORY_SEPARATOR .
            'ConsensusPathDB_human_PPI'
            ;

        $inputFile = $_ENV['build_test_database_path'] . $commonPath;
        $expectedPath = $_ENV['build_test_database_expected_path'] . $commonPath;

        $this->parser = new ConsensusPathDb($inputFile);
        $this->expectedPath = $expectedPath;
    }

    public function testReadRecord() {
        $expected = array();

        if (($handle = fopen($this->expectedPath, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                $expected[] = $data;
            }
            fclose($handle);
        }

        $actual = array();

        foreach ($this->parser as $record) {
            $actual[] = array(
                $record['proteinANamingConvention'],
                $record['proteinAName'],
                $record['proteinBNamingConvention'],
                $record['proteinBName'],
                $record['pubmedId'],
                $record['experimentalSystemType']
            );
        }

        $this->assertEquals($expected, $actual);
    }
}