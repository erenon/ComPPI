<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class Esldb implements LocalizationParserInterface
{
    private $fileName;
    private $fileHandle = null;
    private $currentLine;
    private $currentIdx;
    /**
     * @var array
     */
    private $currentRecord;
    
    private $localizationToGoCode = array (
        'Cytoplasm' => 'GO:0005737',
        'Cell wall' => 'GO:0005618',
        'Golgi' => 'GO:0005794',
        'Vesicles' => 'GO:0031982',
        'Membrane' => 'GO:0016020',
        'Mitochondrion' => 'GO:0005739',
        'Nucleus' => 'GO:0005634',
        'Transmembrane' => 'GO:0016021',
        'Cytoskeleton' => 'GO:0005856',
        'Lysosome' => 'GO:0005764',
        'Endoplasmic reticulum' => 'GO:0005783',
        'Peroxisome' => 'GO:0005777',
        'Secretory pathway' => 'secretory pathway',
        'Secretory' => 'secretory pathway',
        'Vacuole' => 'GO:0005773',
        'Extracellular' => 'GO:0005576',
        'Endosome' => 'GO:0005768'
    );
    
    public function __construct($fileName) {
        $this->fileName = $fileName;
    }
    
    public static function canParseFilename($fileName) {
        // This class is suitable for HS, SC, CE only, DM not supported
        $parsable = array(
            'eSLDB_Saccharomyces_cerevisiae.txt'
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
        
        // trim EOL
        $record = trim($record);
        
        /**
         * 0 => eSLDB code
         * 1 => Original Database Code
         * 2 => Experimental annotation
         * 3 => Prediction
         * 4 => Expreimental system type (computed later)
         * 
         * @var array
         */
        $recordArray = explode("\t", $record);
        
        if (count($recordArray) != 4) {
            throw new \Exception(
            	"Parsed records field count is invalid (" .
                count($recordArray)
                . ")"
            );
        }
        
        if ($recordArray[2] == 'None') {
            // No experimental annotation, use prediction
            $recordArray[2] = $recordArray[3];
            $recordArray[4] = 'SVM decision tree (predicted)';
        } else {
            $recordArray[4] = 'experimental';
        }
        
        $currentLine = array(
            'originalCode' => $recordArray[1],
            'localization' => explode(', ', $recordArray[2]),
            'experimentalSystemType' => $recordArray[4]
        );
        
        $this->currentLine = $currentLine;
    }
    
    private function advanceCursor() {
        $nextLocalization = each($this->currentLine['localization']);
        
        if ($nextLocalization === false) {
            $this->readline();
            $nextLocalization = each($this->currentLine['localization']);
        }
        
        $localization = $nextLocalization['value'];
        $this->currentRecord = array(
            'originalCode' => $this->currentLine['originalCode'],
            'localization' => $localization,
            'experimentalSystemType' => $this->currentLine['experimentalSystemType']
        );
        $this->currentIdx++;
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
        
        // drop header
        fgets($this->fileHandle);
        
        $this->readline();
        $this->advanceCursor();
    }
    
    public function current() {
        $record = $this->currentRecord;
        
        return array(
            'proteinId' => $record['originalCode'],
            'namingConvention' => 'EnsemblPeptideId',
            'localization' => $this->getGoCodeByLocalizationName($record['localization']),
            'pubmedId' => 17108361,
            'experimentalSystemType' => $record['experimentalSystemType']
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