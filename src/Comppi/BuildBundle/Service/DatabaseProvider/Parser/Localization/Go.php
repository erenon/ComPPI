<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class Go extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'go_hs.csv',
        'go_dm.csv',
        'go_sc.csv',
        'go_ce.csv'
    );

    protected $databaseIdentifier = "GO";

    protected function dropHeader() {
    	// Drop lines starting with ! and the true table header
		do {
    		$header = fgets($this->fileHandle);
		} while ($header[0] == '!');
    }

    protected function readRecord() {
        $line = $this->readLine();
        if ($line === false) {
            // EOF
            return;
        }

        /**
		 * 0123456789
         * abcdefghij
         * 
         * 1: uniprotkb-ac name
         * 4: go code
         * 6: exp sys type
         * 8: loc type, only C is needed
         * 
         * @var array
         */
        $recordArray = explode("\t", $line);
        // Seems to be not deterministic
        //$this->checkRecordFieldCount($recordArray, 15);
        
        if ($recordArray[8] != 'C') {
        	return $this->readRecord();
        }

        $this->currentRecord = array(
            'proteinId' => $recordArray[1],
            'namingConvention' => 'UniProtKB-AC',
            'localization' => $recordArray[4],
            'pubmedId' => 10802651,
            'experimentalSystemType' => $recordArray[6]
        );
    }
}