<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class GeneidToUniprot implements MapParserInterface
{
    private $fileName;
    private $fileHandle = null;
    private $currentIdx;
    private $currentRecord;

    private $currentCursorIndex;
    private $recordCursors = array(
        array(
        	'namingConventionA' => 'EntrezGene',
            'namingConventionB'	=> 'UniProtKB-AC',
            'proteinNameA'	=> 2,
            'proteinNameB'	=> 0
        ),
        array(
        	'namingConventionA' => 'UniProtKB-ID',
            'namingConventionB'	=> 'UniProtKB-AC',
            'proteinNameA'	=> 1,
            'proteinNameB'	=> 0
        ),
    );

    public function __construct($fileName) {
        $this->fileName = $fileName;
    }

    static function canParseFilename($fileName) {
        $parsable = array(
            'YEAST_559292_idmapping_selected.tab',
            'CAEEL_6239_idmapping_selected.tab',
            'HUMAN_9606_idmapping_selected.tab',
            'DROME_7227_idmapping_selected.tab'
        );

        return in_array($fileName, $parsable);
    }

    private function readline() {
        $record = fgets($this->fileHandle);

        // end of file
        if (!$record) {
            if (!feof($this->fileHandle)) {
                throw new \Exception("Unexpected error while reading database");
            }
            return;
        }

        $recordArray = explode("\t", $record);

        if (count($recordArray) != 23) {
            throw new \Exception(
            	"Parsed records field count is invalid (" .
                count($recordArray)
                . ")"
            );
        }

        $this->currentRecord = $recordArray;
    }

    private function advanceCursor() {
        if ($this->currentCursorIndex == count($this->recordCursors) - 1) {
            $this->currentCursorIndex = 0;
            $this->readline();
        } else {
            $this->currentCursorIndex++;
        }

        $this->currentIdx++;
    }

    /* Iterator methods */

    public function rewind() {
        if ($this->fileHandle == null) {
            $this->fileHandle = fopen($this->fileName, 'r');
            $this->currentIdx = -1;
        } else {
            rewind($this->fileHandle);
        }

        $this->currentCursorIndex = count($this->recordCursors) - 1;
        $this->advanceCursor();
    }

    public function current() {
        $recordArray = $this->currentRecord;
        $cursor = $this->recordCursors[$this->currentCursorIndex];

        return array(
            'namingConventionA' => $cursor['namingConventionA'],
            'namingConventionB'	=> $cursor['namingConventionB'],
            'proteinNameA'	=> $recordArray[$cursor['proteinNameA']],
            'proteinNameB'	=> $recordArray[$cursor['proteinNameB']]
        );
    }

    public function key() {
        return $this->currentIdx;
    }

    public function next() {
        $this->advanceCursor();
    }

    public function valid() {
        $valid = !feof($this->fileHandle);
        if (!$valid) {
            fclose($this->fileHandle);
        }

        return $valid;
    }
}