<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class Ptarget implements LocalizationParserInterface
{
    private static $minimumProbability = 95;
    
    private $fileName;
    
    /**
     * UniProtKB-ID specie specific ending
     * 
     * There are two uniprot naming conventions:
     * UniProtKB-AC and UniProtKB-AC
     * 
     * pTarget uses them in a mixed way (sad)
     * we can only separate them by depending on
     * UniProtKB-IDs special -- specie specific -- ending.
     * 
     * This variable stores this ending related to the opened file.
     * 
     * @var string
     */
    private $uniprotIdSuffix;
    
    private $fileHandle = null;
    private $currentIdx;
    
    /**
     * @var array
     */
    private $currentRecord;
    
    private $localizationToGoCode = array(
        'cytoplasm' => 'GO:0005737',
        'Endoplasmic_Reticulum' => 'GO:0005783',
        'Extracellular/Secretory' => 'secretory_pathway',
        'Golgi' => 'GO:0005794',
        'Lysosomes' => 'GO:0005764',
        'Mitochondria' => 'GO:0005739',
        'Nucleus' => 'GO:0005634',
        'Peroxysomes' => 'GO:0005777',
        'Plasma_Membrane' => 'GO:0005886',
    );
    
    public function __construct($fileName) {
        $this->fileName = $fileName;
        $this->setupUniprotSuffix(basename($fileName));
    }
    
    private function setupUniprotSuffix($fileName) {
        $suffixes = array (
        	'yeast_preds.txt' => '_YEAST'
        );
        
        if (isset($suffixes[$fileName])) {
            $this->uniprotIdSuffix = $suffixes[$fileName];
        } else {
            // If you got this exception, you have to prepare this driver
            // to handle the file by adding it to the $suffixes array
            throw new \Exception("No uniprot suffix found for file: '" . $fileName . "'.");
        }
    }
    
    public static function canParseFilename($fileName) {
        $parsable = array(
            'yeast_preds.txt'
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
         * 2 => probability
         * 
         * @var array
         */
        $recordArray = preg_split('/ +/', $record);
        
        if (!is_numeric($recordArray[2]) || $recordArray[2] < Ptarget::$minimumProbability) {
            // invalid localization
            return $this->readline();
        } else {
            $this->currentIdx++;
            $this->currentRecord = $recordArray;
        }
        
    }
    
    private function getNamingConventionByName($proteinName) {
        $start = strlen($this->uniprotIdSuffix) * -1;
        if (substr($proteinName, $start) === $this->uniprotIdSuffix) {
            return 'UniProtKB-ID';
        } else {
            return 'UniProtKB-AC';
        }
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
        
        if (count($record) != 3) {
            throw new \Exception(
            	"Parsed records field count is invalid (" .
                count($record)
                . ")"
            );
        }
        
        return array(
            'proteinId' => $record[0],
            'namingConvention' => $this->getNamingConventionByName($record[0]),
            'localization' => $this->getGoCodeByLocalizationName($record[1]),
            'pubmedId' => 16144808,
            'experimentalSystemType' => 'domain projection method'
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