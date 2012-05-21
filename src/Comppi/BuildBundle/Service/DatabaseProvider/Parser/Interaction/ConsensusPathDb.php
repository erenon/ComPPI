<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class ConsensusPathDb extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'ConsensusPathDB_human_PPI',
        'ConsensusPathDB_yeast_PPI'
    );

    private $currentLine;

    protected $headerCount = 2;

    protected function readRecord() {
        if (isset($this->currentLine['proteinPair'])) {
            // advance cursor
            $nextPair = each($this->currentLine['proteinPair']);

            if ($nextPair !== false) {
                $this->currentRecord['proteinAName'] = $nextPair['value'][0];
                $this->currentRecord['proteinBName'] = $nextPair['value'][1];
                return;
            }
        }

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
        $proteinGroups = explode(',', $recordArray[2]);

        $proteinPairArray = array();
        if (count($proteinGroups) == 1) {
            // self interaction
            $proteinPairArray[] = array (
                $proteinGroups[0],
                $proteinGroups[0]
            );
        } else {
            assert(count($proteinGroups) == 2);
            $proteinAGroup = explode('.', $proteinGroups[0]);
            $proteinBGroup = explode('.', $proteinGroups[1]);

            foreach ($proteinAGroup as $proteinA) {
                foreach ($proteinBGroup as $proteinB) {
                    $proteinPairArray[] = array (
                        $proteinA,
                        $proteinB
                    );
                }
            }
        }

        $this->currentLine['proteinPair'] = $proteinPairArray;

        $this->currentRecord = array(
            'proteinANamingConvention' => 'UniProtKB-ID',
            'proteinAName' => $this->currentLine['proteinPair'][0][0],
        	'proteinBNamingConvention' => 'UniProtKB-ID',
            'proteinBName' => $this->currentLine['proteinPair'][0][1],
            'pubmedId' => $pubmedId,
            'experimentalSystemType' => $recordArray[0]
        );

        next($this->currentLine['proteinPair']);
    }
}