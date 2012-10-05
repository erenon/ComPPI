<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

abstract class AbstractMapParser implements MapParserInterface
{
    protected $fileName;
    protected $fileHandle = null;
    protected $currentIdx;
    protected $currentRecord;

    protected static $parsableFileNames = array();
    protected $headerCount = 0;

    public function __construct($fileName) {
        $this->fileName = $fileName;
    }

    static function canParseFilename($fileName) {
        return in_array($fileName, static::$parsableFileNames);
    }

    protected function readline() {
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

    /* Iterator methods */

    public function rewind() {
        if ($this->fileHandle == null) {
            $this->fileHandle = fopen($this->fileName, 'r');

            if ($this->fileHandle === false) {
                throw new \Exception('Failed to open file: ' . $this->fileName);
            }

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