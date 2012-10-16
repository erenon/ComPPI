<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class ComppiStandard extends AbstractMapParser
{
    private $namingConventionA;
    private $namingConventionB;

    private $validMap = true;
    private $invalidLineCount = 0;

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

        if (count($recordArray) != 2) {
            $this->invalidLineCount++;
            return $this->readRecord();
        }

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

        if (count($headerParts) < 2) {
            echo "WARNING: invalid header format in comppi standard map '" .
                $this->fileName . "'\n";
            echo "Header found: '" . $header . "'\n";

            $this->validMap = false;
            return;
        }

        $this->namingConventionA = $headerParts[0];
        $this->namingConventionB = $headerParts[1];

        $this->readRecord();
    }

    public function valid() {
        $valid = !feof($this->fileHandle) && $this->validMap;
        if (!$valid) {
            fclose($this->fileHandle);

            if ($this->invalidLineCount > 0) {
                echo "WARNING: failed to process " . $this->invalidLineCount . "line(s) in file: '"
                    . $this->fileName . "'\n";
            }
        }

        return $valid;
    }
}