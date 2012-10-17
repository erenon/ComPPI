<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class HprdToUniprot extends AbstractMapParser
{
    protected static $parsableFileNames = array(
    	'HPRD_ID_MAPPINGS.txt'
    );

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 8);

        // extract uniprot name
        $delimiterPos = strpos($recordArray[6], ',');
        if ($delimiterPos !== false) {
            // multiple uniprot name found, use the first one
            $uniprotName = substr($recordArray[6], 0, $delimiterPos);
        } else {
            // only one uniprot name, do nothing special
            $uniprotName = $recordArray[6];
        }

        if ($uniprotName == '-') {
            // no uniprot name provided, drop record
            return $this->readRecord();
        }

        $this->currentRecord = array (
            'namingConventionA' => 'Hprd',
            'namingConventionB'	=> 'UniProtKB-AC',
            'proteinNameA'	=> $recordArray[0],
            'proteinNameB'	=> $uniprotName
        );
    }
}