<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class CcsbSc extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'Y2H_union.txt',
    );

    protected $databaseIdentifier = "CCSB";

    protected $headerCount = 0;

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }

        $this->unfilteredEntryCount++;

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 2);

        $this->currentRecord = array(
            'proteinANamingConvention' => 'EnsemblPeptideId',
            'proteinAName' => $recordArray[0],
        	'proteinBNamingConvention' => 'EnsemblPeptideId',
            'proteinBName' => $recordArray[1],
            'pubmedId' => 18719252,
            'experimentalSystemType' => 'HQ Y2H'
        );
    }
}