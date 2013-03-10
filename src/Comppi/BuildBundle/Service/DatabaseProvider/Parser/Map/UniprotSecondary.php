<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class UniprotSecondary extends AbstractMapParser
{
    protected static $parsableFileNames = array(
    	'sec_ac.txt'
    );

    protected $headerCount = 30;

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = preg_split('/ +/', $line);
        $this->checkRecordFieldCount($recordArray, 2);

        $this->currentRecord = array (
            'namingConventionA' => 'UniProtKB-AC',
            'namingConventionB'	=> 'UniProtKB-AC',
            'proteinNameA'	=> $recordArray[0],
            'proteinNameB'	=> $recordArray[1]
        );
    }
}