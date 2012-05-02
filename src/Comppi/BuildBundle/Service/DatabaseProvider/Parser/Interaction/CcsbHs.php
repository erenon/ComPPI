<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class CcsbHs extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'HI2_2011.tsv',
    );

    protected $headerCount = 1;

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }


        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 4);

        $this->currentRecord = array(
            'proteinANamingConvention' => 'CcsbHs',
            'proteinAName' => $recordArray[1],
        	'proteinBNamingConvention' => 'CcsbHs',
            'proteinBName' => $recordArray[3],
            'pubmedId' => 21516116,
            'experimentalSystemType' => 'HQ Y2H'
        );
    }
}