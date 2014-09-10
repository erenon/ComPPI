<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class MatrixDb extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'MatrixDB_20120420.txt'
    );

    protected $databaseIdentifier = "MatrixDB";

    protected $headerCount = 1;

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
            $this->checkRecordFieldCount($recordArray, 24);

            if ($recordArray[9]  !== 'taxid:9606(Human)'
            ||  $recordArray[10] !== 'taxid:9606(Human)')
            {
                // interspecie interaction or
                // specie not specified
                // drop record
                $validRead = false;
                continue;
            } else {
                $validRead = true;
            }

            $proteinAName = $this->extractProteinFromField($recordArray[0]);
            $proteinBName = $this->extractProteinFromField($recordArray[1]);

            if ($proteinAName === false || $proteinBName === false) {
                // no uniprot name found
                $validRead = false;
                continue;
            } else {
                $validRead = true;
            }

            $pubmedId = $this->extractPubmedFromField($recordArray[8]);

            // extract experimental system type
            $expSysStart = strpos($recordArray[6], '(') + 1;
            $expSysEnd = strpos($recordArray[6], ')');

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

    protected function extractProteinFromField($field) {
        $names = explode('|', $field);
        foreach ($names as $name) {
            if (substr($name, 0, 10) == 'uniprotkb:') {
                return substr($name, 10);
            }
        }

        return false;
    }

    protected function extractPubmedFromField($field) {
        $names = explode('|', $field);
        foreach ($names as $name) {
            if (substr($name, 0, 7) == 'pubmed:') {
                return substr($name, 7);
            }
        }

        return 0;
    }
}