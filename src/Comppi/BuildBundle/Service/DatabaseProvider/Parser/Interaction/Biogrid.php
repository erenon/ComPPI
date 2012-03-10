<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class Biogrid implements InteractionParserInterface
{
    private $fileName;
    private $fileHandle = null;
    private $currentIdx;
    private $currentLine;
    
    public function __construct($fileName) {
        $this->fileName = $fileName;
    }
    
    public static function canParseFilename($fileName) {
        $parsable = array(
            'BIOGRID-ORGANISM-Saccharomyces_cerevisiae-3.1.81.tab2.txt'
        );
        
        return in_array($fileName, $parsable);
    }
    
    public function getDatabaseIdentifier() {
        return basename($this->fileName);
    }
    
    public function getDatabaseNamingConvention() {
        return 'EntrezGene';
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
        
        // drop header
        fgets($this->fileHandle);
        
        $this->readline();
    }
    
    public function current() {
        $recordArray = explode("\t", $this->currentLine);
        
        if (count($recordArray) != 24) {
            throw new \Exception(
            	"Parsed records field count is invalid (" .
                count($recordArray)
                . ")"
            );
        }
        
        return array(
            'proteinAName' => $recordArray[1],
            'proteinBName' => $recordArray[2],
            'pubmedId' => $recordArray[15],
            'experimentalSystemType' => $recordArray[13]
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