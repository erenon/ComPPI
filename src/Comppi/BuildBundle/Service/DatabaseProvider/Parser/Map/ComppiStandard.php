<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class ComppiStandard extends AbstractMapParser
{
    private $namingConventionA;
    private $namingConventionB;

    /**
     * Parses file if fileName starts with comppi
     * @param string $fileName
     */
    static function canParseFilename($fileName) {
        $prefix = 'comppi';
        return $prefix == substr($fileName, 0, strlen($prefix));
    }

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 2);

        $this->currentRecord = array (
            'namingConventionA' => $this->namingConventionA,
            'namingConventionB'	=> $this->namingConventionB,
            'proteinNameA'	=> $recordArray[0],
            'proteinNameB'	=> $recordArray[1]
        );
    }

    /* Iterator methods */

    public function rewind() {
        if ($this->fileHandle == null) {
            $this->fileHandle = fopen($this->fileName, 'r');
            $this->currentIdx = -1;
        } else {
            rewind($this->fileHandle);
        }

        // read headers
        $header = fgets($this->fileHandle);

        // trim EOL
        $header = trim($header);

        $headerParts = explode("\t", $header);

        $this->namingConventionA = $headerParts[0];
        $this->namingConventionB = $headerParts[1];

        $this->readRecord();
    }
}