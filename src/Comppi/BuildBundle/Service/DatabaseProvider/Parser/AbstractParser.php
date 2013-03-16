<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser;

abstract class AbstractParser
{
    protected static $parsableFileNames = array();

    public static function canParseFilename($fileName) {
        return in_array($fileName, static::$parsableFileNames);
    }

    protected $databaseIdentifier = null;

    protected $headerCount = 0;

    protected $fileName;
    protected $fileHandle = null;

    protected $currentIdx;
    protected $currentRecord;

    public function __construct($fileName) {
        $this->fileName = $fileName;

        if ($this->databaseIdentifier == null) {
            $fullName = get_class($this);
            $this->databaseIdentifier = substr($fullName, strrpos($fullName, '\\') + 1);
        }
    }

    public function getDatabaseIdentifier() {
        return $this->databaseIdentifier;
    }

    public function getFileInfo() {
        return $this->fileName;
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

    protected function dropHeader() {
        for ($i = 0; $i < $this->headerCount; $i++) {
            fgets($this->fileHandle);
        }
    }

    protected abstract function readRecord();

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

        $this->dropHeader();

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