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
            $subgroups = array();
            foreach ($proteinGroups as $group) {
                $subgroups[] = explode('.', $group);
            }

            for ($i = 0; $i < count($subgroups) - 1; $i++) {
                foreach ($subgroups[$i] as $proteinA) {
                    for ($j = $i + 1; $j < count($subgroups); $j++) {
                        foreach ($subgroups[$j] as $proteinB) {
                            $proteinPairArray[] = array (
                                $proteinA,
                                $proteinB
                            );
                        }
                    }
                }
            }
        }

        $systemTypes = explode(',', $recordArray[0]);

        $this->currentLine['proteinPair'] = $proteinPairArray;
        
        $this->unfilteredEntryCount += count($proteinPairArray);

        $this->currentRecord = array(
            'proteinANamingConvention' => 'UniProtKB-ID',
            'proteinAName' => $this->currentLine['proteinPair'][0][0],
        	'proteinBNamingConvention' => 'UniProtKB-ID',
            'proteinBName' => $this->currentLine['proteinPair'][0][1],
            'pubmedId' => $pubmedId,
            'experimentalSystemType' => $systemTypes
        );

        next($this->currentLine['proteinPair']);
    }
}