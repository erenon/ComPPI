<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class Pips extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'PredictedInteractions1000.txt'
    );

    protected $headerCount = 1;

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 5);

        $this->currentRecord = array(
            'proteinANamingConvention' => 'UniprotGeneName',
            'proteinAName' => $recordArray[0],
            'proteinBNamingConvention' => 'UniprotGeneName',
            'proteinBName' => $recordArray[2],
            'pubmedId' => 18988626,
            'experimentalSystemType' => 'predicted'
        );
    }
}