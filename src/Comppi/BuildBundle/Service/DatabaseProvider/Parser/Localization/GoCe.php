<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class GoCe extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'go_ce.tsv',
    );

    protected $hasHeader = true;

    protected function readRecord() {
        $line = $this->readLine();
        if ($line === false) {
            // EOF
            return;
        }

        /**
         * 0 => GO Term Accession (localization)
         * 1 => WB Gene ID
         *
         * @var array
         */
        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 2);

        $this->currentRecord = array(
            'proteinId' => $recordArray[1],
            'namingConvention' => 'WBGeneId',
            'localization' => $recordArray[0],
            'pubmedId' => 10802651,
            'experimentalSystemType' => 'not available'
        );
    }
}