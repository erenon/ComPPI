<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class Bacello implements LocalizationParserInterface
{
    private $fileName;
    private $fileHandle = null;
    private $currentIdx;
    /**
     * @var array
     */
    private $currentRecord;
    
    private $localizationToGoCode = array (
        'Cytoplasm' => 'GO:0005737',
        'Secretory' => 'secretory_pathway',
        'Mitochondrion' => 'GO:0005739',
        'Nucleus' => 'GO:0005634'
    );
    
    public function __construct($fileName) {
        $this->fileName = $fileName;
    }
    
    public static function canParseFilename($fileName) {
        $parsable = array(
            'pred_sce'
        );
        
        return in_array($fileName, $parsable);
    }
    
    public function getDatabaseIdentifier() {
        return basename($this->fileName);
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
        
        $record = trim($record);
        
        /**
         * 0 => proteinId
         * 1 => localization
         * 
         * @var array
         */
        $recordArray = preg_split('/ +/', $record);
        
        $this->currentIdx++;
        $this->currentRecord = $recordArray;
    }
    
    private function getGoCodeByLocalizationName($localization) {
        if (isset($this->localizationToGoCode[$localization])) {
            return $this->localizationToGoCode[$localization];
        } else {
            throw new \InvalidArgumentException("No GO code found for localization: '" . $localization . "'");
        }
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
        $record = $this->currentRecord;
        
        if (count($record) != 2) {
            throw new \Exception(
            	"Parsed records field count is invalid (" .
                count($record)
                . ")"
            );
        }
        
        return array(
            'proteinId' => $record[0],
            'namingConvention' => 'EnsemblPeptideId',
            'localization' => $this->getGoCodeByLocalizationName($record[1]),
            'pubmedId' => 16873501,
            'experimentalSystemType' => 'SVM decision tree (predicted)'
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