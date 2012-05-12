<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

abstract class AbstractDroid extends AbstractInteractionParser
{
    protected $headerCount = 1;

    protected $pubmedColIdx;
    protected $expSysTypeColIdx;

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = explode("\t", $line);

        $pubmedId = $this->formatPubmed($recordArray[$this->pubmedColIdx]);
        $expSysType = $this->formatExpSysType($recordArray[$this->expSysTypeColIdx]);

        $this->currentRecord = array(
            'proteinANamingConvention' => 'EnsemblGeneId',
            'proteinAName' => $recordArray[0],
            'proteinBNamingConvention' => 'EnsemblGeneId',
            'proteinBName' => $recordArray[1],
            'pubmedId' => $pubmedId,
            'experimentalSystemType' => $expSysType
        );
    }

    protected abstract function formatPubmed($field);
    protected abstract function formatExpSysType($field);
}