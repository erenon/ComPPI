<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class Hprd extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'BINARY_PROTEIN_PROTEIN_INTERACTIONS.txt',
    );

    protected $databaseIdentifier = "HPRD";

    protected $headerCount = 0;

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }
        
        $this->unfilteredEntryCount++;

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 8);

        $systemTypes = explode(';', $recordArray[6]);

        foreach ($systemTypes as $i => $systemType) {
            $systemTypes[$i] = trim($systemType);
        }

        $this->currentRecord = array(
            'proteinANamingConvention' => 'Hprd',
            'proteinAName' => $recordArray[1],
            'proteinBNamingConvention' => 'Hprd',
            'proteinBName' => $recordArray[4],
            'pubmedId' => $recordArray[7],
            'experimentalSystemType' => $systemTypes
        );
    }
}