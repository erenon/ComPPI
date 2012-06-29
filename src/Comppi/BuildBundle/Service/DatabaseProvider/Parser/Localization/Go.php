<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class Go extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'go_hs.csv',
        'go_dm.csv',
        'go_sc.csv'
    );

    protected $hasHeader = true;

    protected function readRecord() {
        $line = $this->readLine();
        if ($line === false) {
            // EOF
            return;
        }

        /**
         * 0 => Ensembl Protein ID
         * 1 => GO Term Accession (localization)
         *
         * @var array
         */
        $recordArray = explode(',', $line);
        $this->checkRecordFieldCount($recordArray, 2);

        $this->currentRecord = array(
            'proteinId' => $recordArray[0],
            'namingConvention' => 'EnsemblPeptideId',
            'localization' => $recordArray[1],
            'pubmedId' => 10802651,
            'experimentalSystemType' => 'not available'
        );
    }
}