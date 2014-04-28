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
         * 0 => Ensembl Gene ID
         * 1 => GO Term Accession (localization)
         *
         * @var array
         */
        $recordArray = explode(',', $line);
        $this->checkRecordFieldCount($recordArray, 2);

        $this->currentRecord = array(
            'proteinId' => $recordArray[0],
            'namingConvention' => 'EnsemblGeneId',
            'localization' => $recordArray[1],
            'pubmedId' => 10802651,
            'experimentalSystemType' => 'not available'
        );
    }
}