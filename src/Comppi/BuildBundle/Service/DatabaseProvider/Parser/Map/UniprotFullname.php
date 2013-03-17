<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class UniprotFullname extends AbstractMapParser
{
    protected $altNameBlacklist = array(
        'Fragment',
        'Fragments'
    );

    protected $fullNameBlacklist = array(
        '312',
        'Alpha',
        'Letha',
        'NA',
        'Peptide-',
        'Probable tRN',
        'Protein'
    );

    protected $headerCount = 1;

    protected static $parsableFileNames = array(
        'uniprot_hs_fullname.tab',
        'uniprot_dm_fullname.tab',
        'uniprot_sc_fullname.tab',
        'uniprot_ce_fullname.tab'
    );

    protected $recordReady = array();

    protected function readRecord() {
        if (!empty($this->recordReady)) {
            $this->currentRecord = array_shift($this->recordReady);
            return;
        }

        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 4);

        $strippedName = $this->stripNameMeta($recordArray[3]);
        $names = $this->extractNames($strippedName);

        foreach ($names['alt'] as $altName) {
            if (in_array($altName, $this->altNameBlacklist) === false) {
                $this->recordReady[] = array(
                    'namingConventionA'	=> 'UniProtAlt',
                    'namingConventionB' => 'UniProtKB-AC',
                    'proteinNameA'	=> $altName,
                    'proteinNameB'	=> $recordArray[0]
                );
            }
        }

        if (isset($names['full'])) {
            $fullName = $names['full'];

            if (in_array($fullName, $this->fullNameBlacklist) === false) {
                $this->recordReady[] = array(
                    'namingConventionA'	=> 'UniProtFull',
                    'namingConventionB' => 'UniProtKB-AC',
                    'proteinNameA'	=> $fullName,
                    'proteinNameB'	=> $recordArray[0]
                );
            }
        }

        if ($recordArray[2] == 'reviewed') {
            $this->currentRecord = array(
                'namingConventionA' => 'UniProtKB-AC',
                'namingConventionB'	=> 'UniProtKB/Swiss-Prot',
                'proteinNameA'	=> $recordArray[0],
                'proteinNameB'	=> $recordArray[0]
            );
        } else {
            $this->currentRecord = array(
                'namingConventionA' => 'UniProtKB-AC',
                'namingConventionB'	=> 'UniProtKB/TrEmbl',
                'proteinNameA'	=> $recordArray[0],
                'proteinNameB'	=> $recordArray[0]
            );
        }
    }

    private function stripNameMeta($name) {
        // strip [Cleaved into
        $cleavedPos = strpos($name, ' [Cleaved into');
        if ($cleavedPos !== false) {
            $name = substr($name, 0, $cleavedPos);
        }

        // strip [Includes
        $includesPos = strpos($name, ' [Includes');
        if ($includesPos !== false) {
            $name = substr($name, 0, $includesPos);
        }

        return $name;
    }

    private function extractNames($strippedName) {
        $names = array();
        $names['alt'] = array();

        $len = strlen($strippedName);

        if ($strippedName[$len - 1] !== ')') {
            // no alt name found
            $names['full'] = $strippedName;
            return $names;
        }

        // extract alt names
        $parentCount = 0;
        $fullNameEnd = 0;
        for ($i = strlen($strippedName) - 1; $i >= 0; $i--) {
            if ($strippedName[$i] === ')') {
                if ($parentCount == 0) {
                    $altNameEnd = $i - 1;
                }

                $parentCount++;
            } else if ($strippedName[$i] === '(') {
                $parentCount--;

                if ($parentCount == 0) {
                    // start of name found
                    $names['alt'][] = substr($strippedName, $i+1, $altNameEnd - $i);

                    if ($i >= 2) {
                        // check for other alt names
                        if ($strippedName[$i - 2] !== ')') {
                            // no other alt name found, mark and terminate
                            $fullNameEnd = $i - 1;
                            $i = -1;
                        }
                    } else {
                        // no full name provided
                        $fullNameEnd = 0;
                        $i = -1;
                    }
                }
            }
        }

        if ($fullNameEnd > 0) { // full name found
            $names['full'] = substr($strippedName, 0, $fullNameEnd);
        }

        return $names;
    }
}