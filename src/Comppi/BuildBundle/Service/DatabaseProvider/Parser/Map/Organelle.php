<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class Organelle extends AbstractMapParser
{
    protected static $parsableFileNames = array(
        'drosi_organelle.csv',
        'human_organelle.csv',
        'worm_organelle.csv',
        'yeast_organelle.csv'
    );

    protected $headerCount = 1;

    protected $lastOrganelleName = false;

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = explode(",", $line);
        $this->checkRecordFieldCount($recordArray, 4);

        foreach ($recordArray as $field) {
            if (empty($field)) {
                // empty field found, drop record
                return $this->readRecord();
            }
        }

        if ($recordArray[1] !== 'SWISSPROT' || $recordArray[3] !== 'identical') {
            // weak translation, drop record
            return $this->readRecord();
        }

        $uniprotName = $recordArray[2];

        // erease the part of name after . or -
        if (strpos($uniprotName, '.') !== false) {
            $uniprotName = substr($uniprotName, 0, strpos($uniprotName, '.'));
        } else if (strpos($uniprotName, '-') !== false) {
            $uniprotName = substr($uniprotName, 0, strpos($uniprotName, '-'));
        }

        if ($uniprotName === $this->lastOrganelleName) {
            // already got translation, drop record
            return $this->readRecord();
        } else {
            $this->lastOrganelleName = $uniprotName;
        }

        $this->currentRecord = array (
            'namingConventionA' => 'Organelle',
            'namingConventionB'	=> 'UniProtKB-AC',
            'proteinNameA'	=> $recordArray[0],
            'proteinNameB'	=> $uniprotName
        );
    }
}