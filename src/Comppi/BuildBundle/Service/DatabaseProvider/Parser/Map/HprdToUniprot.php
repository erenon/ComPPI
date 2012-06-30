<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class HprdToUniprot implements MapParserInterface
{
    private $fileName;
    private $fileHandle = null;
    private $currentIdx;
    private $currentRecord;

    protected $headerCount = 0;

    public function __construct($fileName) {
        $this->fileName = $fileName;
    }

    static function canParseFilename($fileName) {
        $parsable = array(
            'HPRD_ID_MAPPINGS.txt'
        );

        return in_array($fileName, $parsable);
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
        $this->checkRecordFieldCount($recordArray, 8);

        // extract uniprot name
        $delimiterPos = strpos($recordArray[6], ',');
        if ($delimiterPos !== false) {
            // multiple uniprot name found, use the first one
            $uniprotName = substr($recordArray[6], 0, $delimiterPos);
        } else {
            // only one uniprot name, do nothing special
            $uniprotName = $recordArray[6];
        }

        if ($uniprotName == '-') {
            // no uniprot name provided, drop record
            return $this->readRecord();
        }

        $this->currentRecord = array (
            'namingConventionA' => 'Hprd',
            'namingConventionB'	=> 'UniProtKB-AC',
            'proteinNameA'	=> $recordArray[0],
            'proteinNameB'	=> $uniprotName
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

        // drop headers
        for ($i = 0; $i < $this->headerCount; $i++) {
            fgets($this->fileHandle);
        }

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