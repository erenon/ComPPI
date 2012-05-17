<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class Hprd extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'BINARY_PROTEIN_PROTEIN_INTERACTIONS.txt',
    );

    protected $headerCount = 0;

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 8);

        $this->currentRecord = array(
            'proteinANamingConvention' => 'Hprd',
            'proteinAName' => $recordArray[1],
            'proteinBNamingConvention' => 'Hprd',
            'proteinBName' => $recordArray[4],
            'pubmedId' => $recordArray[7],
            'experimentalSystemType' => $recordArray[6]
        );
    }
}