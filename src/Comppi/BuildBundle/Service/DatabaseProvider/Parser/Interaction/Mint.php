<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class Mint extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        '2012-02-06-mint-Caenorhabditis-binary.mitab26.txt',
        '2012-02-06-mint-Drosophila-binary.mitab26.txt',
        '2012-02-06-mint-human-binary.mitab26.txt',
        '2012-02-06-mint-Saccharomyces-binary.mitab26.txt'
    );

    protected $headerCount = 1;

    protected function readRecord() {
        $validRead = false;

        while (!$validRead && !feof($this->fileHandle)) {
            $line = $this->readLine();

            if ($line === false) {
                // EOF
                return;
            }

            $recordArray = explode("\t", $line);
            $this->checkRecordFieldCount($recordArray, 41);

            $proteinA = $this->extractName($recordArray[0]);
            $proteinB = $this->extractName($recordArray[1]);

            if ($proteinA === false || $proteinB === false) {
                $validRead = false;
                continue;
            } else {
                $validRead = true;
            }

            // 7: strlen('pubmed:')
            $pubmedId = substr($recordArray[8], 7);

            // extract experimental system type
            $expSysStart = strpos($recordArray[6], '(') + 1;
            $expSysEnd = strpos($recordArray[6], ')');

            $expSysType = substr($recordArray[6], $expSysStart, $expSysEnd-$expSysStart);

            // omit genetic records
            if ($expSysType == 'genetic' || $expSysType == 'genetic interference') {
                $validRead = false;
                continue;
            }

            $this->currentRecord = array(
                'proteinANamingConvention' => $proteinA[0],
                'proteinAName' => $proteinA[1],
                'proteinBNamingConvention' => $proteinB[0],
                'proteinBName' => $proteinB[1],
                'pubmedId' => $pubmedId,
                'experimentalSystemType' => $expSysType
            );
        }
    }

    protected function extractName($field) {
        $separatorPos = strpos($field, ':');

        if ($separatorPos === false) {
            return false;
        }

        $namingConvention = substr($field, 0, $separatorPos);

        if ($namingConvention == 'uniprotkb') {
            $namingConvention = 'UniProtKB-AC';
        }

        // +1: omit the separator itself
        $proteinName = substr($field, $separatorPos + 1);

        return array(
            $namingConvention,
            $proteinName
        );
    }
}