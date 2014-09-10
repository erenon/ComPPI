<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

/**
 * This parser uses the stripped-by-specie versions of the original database
 * @see intact-filter.sh
 */
class Intact extends AbstractInteractionParser
{
    // stripped versions of the original database
    protected static $parsableFileNames = array(
        'intact-ce.txt',
        'intact-dm.txt',
        'intact-hs.txt',
        'intact-sc.txt'
    );

    protected $databaseIdentifier = "IntAct";

    // no header in the stripped versions
    protected $headerCount = 0;

    protected function readRecord() {
        $validRead = false;

        while (!$validRead) {
            $line = $this->readLine();

            if ($line === false) {
                // EOF
                return;
            }
            
            $this->unfilteredEntryCount++;

            $recordArray = explode("\t", $line);
            $this->checkRecordFieldCount($recordArray, 42);

            // check for same taxid:
            if ($recordArray[9] !== $recordArray[10]) {
                $validRead = false;
                continue;
            } else {
                $validRead = true;
            }

            // 10: strlen('uniprotkb:')
            $proteinAName = $this->getProteinFromField($recordArray[0]);
            $proteinBName = $this->getProteinFromField($recordArray[1]);

            if ($proteinAName === false || $proteinBName === false) {
                $validRead = false;
                continue;
            } else {
                $validRead = true;
            }

            $pubmedId = $this->getPubmedFromField($recordArray[8]);

            // extract experimental system type
            $expSysStart = strpos($recordArray[6], '(') + 1;
            $expSysEnd = strpos($recordArray[6], ')');

            if ($expSysEnd === false) {
                $expSysStart = strpos($recordArray[6], '"') + 1;
                $expSysEnd = strrpos($recordArray[6], '"');
            }

            $expSysType = substr($recordArray[6], $expSysStart, $expSysEnd-$expSysStart);

            $this->currentRecord = array(
                'proteinANamingConvention' => 'UniProtKB-AC',
                'proteinAName' => $proteinAName,
                'proteinBNamingConvention' => 'UniProtKB-AC',
                'proteinBName' => $proteinBName,
                'pubmedId' => $pubmedId,
                'experimentalSystemType' => $expSysType
            );
        }
    }

    protected function getProteinFromField($field) {
        $names = explode('|', $field);
        foreach ($names as $name) {
            if (substr($name, 0, 10) == 'uniprotkb:') {
                return substr($name, 10);
            }
        }

        return false;
    }

    protected function getPubmedFromField($field) {
        // 7: strlen('pubmed:')
        $start = strpos($field, 'pubmed:') + 7;
        $end = strpos($field, '|', $start);

        if ($end === false) {
            return substr($field, $start);
        } else {
            return substr($field, $start, $end);
        }
    }
}