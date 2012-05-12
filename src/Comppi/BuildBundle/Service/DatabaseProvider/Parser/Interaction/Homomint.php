<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class Homomint extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'homomint-full.mitab25.txt-binary.mitab26',
    );

    protected $headerCount = 2;

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 40);

        // 10: strlen('uniprotkb:')
        $proteinAName = substr($recordArray[0], 10);
        $proteinBName = substr($recordArray[1], 10);

        // 7: strlen('pubmed:')
        $pubmedId = substr($recordArray[8], 7);

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