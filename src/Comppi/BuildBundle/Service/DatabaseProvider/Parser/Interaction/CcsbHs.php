<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class CcsbHs extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'HI2_2011.tsv',
    );

    protected $databaseIdentifier = "CCSB";

    protected $headerCount = 1;

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }
        
        $this->unfilteredEntryCount++;

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 4);

        $this->currentRecord = array(
            'proteinANamingConvention' => 'UniprotGeneName',
            'proteinAName' => $recordArray[1],
        	'proteinBNamingConvention' => 'UniprotGeneName',
            'proteinBName' => $recordArray[3],
            'pubmedId' => 21516116,
            'experimentalSystemType' => 'HQ Y2H'
        );
    }
}