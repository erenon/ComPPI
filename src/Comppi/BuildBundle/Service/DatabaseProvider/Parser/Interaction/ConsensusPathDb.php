<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class ConsensusPathDb extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'ConsensusPathDB_human_PPI',
        'ConsensusPathDB_yeast_PPI'
    );

    protected $headerCount = 2;

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 3);

        // store the first pubmedId
        // losing information here
        $pubmedParts = explode(',', $recordArray[1]);
        $pubmedId = $pubmedParts[0];

        // Permutate proteins here
        // TODO

        $this->currentRecord = array(
            'proteinANamingConvention' => 'UniprotSwissprot',
            'proteinAName' => 'ConsensusPathDb Not implemented',
        	'proteinBNamingConvention' => 'UniprotSwissprot',
            'proteinBName' => 'ConsensusPathDb Not implemented',
            'pubmedId' => $pubmedId,
            'experimentalSystemType' => $recordArray[0]
        );
    }
}