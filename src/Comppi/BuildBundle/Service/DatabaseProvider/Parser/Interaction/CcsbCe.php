<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class CcsbCe extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'wi8.txt',
    );

    protected $databaseIdentifier = "CCSB";

    protected $headerCount = 1;

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }


        $recordArray = explode("\t", $line);

        $this->currentRecord = array(
            'proteinANamingConvention' => 'EnsemblGeneId',
            'proteinAName' => $recordArray[0],
        	'proteinBNamingConvention' => 'EnsemblGeneId',
            'proteinBName' => $recordArray[1],
            'pubmedId' => 19123269,
            'experimentalSystemType' => 'HQ Y2H'
        );
    }
}