<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

abstract class AbstractLocalizationParser implements LocalizationParserInterface
{
    protected static $parsableFileNames = array();
    
    protected $fileName;
    protected $localizationToGoCode = array();
    protected $hasHeader = false;
    
    protected $fileHandle = null;
    protected $currentIdx;
    protected $currentRecord;
    
    public function __construct($fileName) {
        $this->fileName = $fileName;
    }
    
    public static function canParseFilename($fileName) {       
        return in_array($fileName, static::$parsableFileNames);
    }
    
    public function getDatabaseIdentifier() {
        return basename($this->fileName);
    }
    
    protected function getGoCodeByLocalizationName($localization) {
        if (isset($this->localizationToGoCode[$localization])) {
            return $this->localizationToGoCode[$localization];
        } else {
            throw new \InvalidArgumentException("No GO code found for localization: '" . $localization . "'");
        }
    }
    
    protected function readLine() {
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
    
    protected abstract function readRecord();
    
    /* Iterator methods */
    
    public function rewind() {
        if ($this->fileHandle == null) {
            $this->fileHandle = fopen($this->fileName, 'r');
            $this->currentIdx = 0;
        } else {
            rewind($this->fileHandle);
        }
        
        if ($this->hasHeader) {
            // drop header
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