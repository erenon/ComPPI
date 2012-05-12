<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class Dip extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'Celeg20120228.txt',
        'Dmela20120228.txt',
        'Hsapi20120228.txt',
        'Scere20120228.txt'
    );

    protected $headerCount = 1;

    protected function readRecord() {
        $isValidRead = false;

        while(!$isValidRead && !feof($this->fileHandle)) {
            $line = $this->readLine();

            if ($line === false) {
                // EOF
                return;
            }

            $recordArray = explode("\t", $line);
            $this->checkRecordFieldCount($recordArray, 18);

            $proteinA = $this->getProteinFromField($recordArray[0]);
            if ($proteinA == false) {
                $isValidRead = false;
                continue; // main while
            }

            $proteinB = $this->getProteinFromField($recordArray[1]);
            if ($proteinB == false) {
                $isValidRead = false;
                continue; // main while
            }

            $isValidRead = true;

            $expSysTypeRaw = $recordArray[6] . $recordArray[11];
            preg_match_all("/MI\:\d+\((.+?)\)/", $expSysTypeRaw, $expSysTypes);
            $expSysType = implode(', ', $expSysTypes[1]);

            $this->currentRecord = array(
                'proteinANamingConvention' => $proteinA['namingConvention'],
                'proteinAName' => $proteinA['name'],
                'proteinBNamingConvention' => $proteinB['namingConvention'],
                'proteinBName' => $proteinB['name'],
                'pubmedId' => $this->getPubmedFromField($recordArray[8]),
                'experimentalSystemType' => $expSysType
            );
        }

    }

    protected function getProteinFromField($field) {
        $names = explode('|', $field);
        if (count($names) == 1) {
            assert(substr($names[0], 0, 4) == 'DIP-');
            // only DIP id found, drop record
            return false;
        } else if (count($names) == 2) {
            $name = substr($names[1], strlen('refseq:'));
            return array (
                'name' => $name,
                'namingConvention' => 'refseq'
            );
        } else {
            $name = substr($names[2], strlen('uniprotkb:'));
            return array (
                'name' => $name,
                'namingConvention' => 'UniProtKB-AC'
            );
        }
    }

    protected function getPubmedFromField($field) {
        $firstPubmedString = substr($field, 0, strpos($field, '|'));
        $pubmed = substr($firstPubmedString, strlen('pubmed:'));

        return $pubmed;
    }
}