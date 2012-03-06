<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class GeneidToUniprot implements MapParserInterface
{
    private $fileName;
    private $fileHandle = null;
    private $currentIdx;
    private $currentLine;
    
    public function __construct($fileName) {
        $this->fileName = $fileName;
    }
    
    static function canParseFilename($fileName) {
        $parsable = array(
            'YEAST_559292_idmapping_selected.tab'
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
        
        $this->currentIdx++;
        $this->currentLine = $record;
    }
    
    /* Iterator methods */
    
    public function rewind() {
        if ($this->fileHandle == null) {
            $this->fileHandle = fopen($this->fileName, 'r');
            $this->currentIdx = -1;
        } else {
            rewind($this->fileHandle);
        }
        
        $this->readline();
    }
    
    public function current() {
        $recordArray = explode("\t", $this->currentLine);
        
        if (count($recordArray) != 23) {
            throw new \Exception(
            	"Parsed records field count is invalid (" .
                count($recordArray)
                . ")"
            );
        }
        
        return array(
            'namingConventionA' => 'Geneid',
            'namingConventionB'	=> 'Uniprot',
            'proteinNameA'	=> $recordArray[2],
            'proteinNameB'	=> $recordArray[0]    // UniProtKB-AC
        );
    }
    
    public function key() {
        return $this->currentIdx;
    }
    
    public function next() {
        $this->readline();
    }
    
    public function valid() {
        $valid = !feof($this->fileHandle);
        if (!$valid) {
            fclose($this->fileHandle);
        }
        
        return $valid;
    }
}