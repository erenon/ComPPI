<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class ComppiStandard implements MapParserInterface
{
    private $fileName;
    private $fileHandle = null;
    private $currentIdx;
    private $currentRecord;

    private $namingConventionA;
    private $namingConventionB;

    public function __construct($fileName) {
        $this->fileName = $fileName;
    }

    /**
     * Parses file if fileName starts with comppi
     * @param string $fileName
     */
    static function canParseFilename($fileName) {
        $prefix = 'comppi';
        return $prefix == substr($fileName, 0, strlen($prefix));
    }

    private function readline() {
        $line = fgets($this->fileHandle);

        // end of file
        if (!$line) {
            if (!feof($this->fileHandle)) {
                throw new \Exception("Unexpected error while reading database");
            }
            return false;
        }

        // trim EOL
        $line = trim($line);

        if ($line == "") {
            return $this->readline();
        }

        return $line;
    }

    protected function checkRecordFieldCount($recordArray, $expectedCount) {
        if (count($recordArray) != $expectedCount) {
            throw new \Exception(
                "Parsed records field count is invalid (" .
                count($recordArray)
                . ")"
            );
        }
    }

    private function readRecord() {
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
        $headerParts = explode("\t", $header);

        $this->namingConventionA = $headerParts[0];
        $this->namingConventionB = $headerParts[1];

        $this->readRecord();
    }

    public function current() {
        return $this->currentRecord;
    }

    public function key() {
        return $this->currentIdx;
    }

    public function next() {
        $this->currentIdx++;
        $this->readRecord();
    }

    public function valid() {
        $valid = !feof($this->fileHandle);
        if (!$valid) {
            fclose($this->fileHandle);
        }

        return $valid;
    }
}