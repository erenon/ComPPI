<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class Esldb extends AbstractLocalizationParser
{
    // This class is suitable for HS, SC, CE only, DM not supported
    // Do not add DM files here
    protected static $parsableFileNames = array(
        'eSLDB_Saccharomyces_cerevisiae.txt'
    );
    
    private $currentLine;
    
    protected $localizationToGoCode = array (
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
    
    protected $hasHeader = true;
    
    protected function readRecord() {
        if (isset($this->currentLine['localization'])) {
            // advance cursor
            $nextLocalization = each($this->currentLine['localization']);
            
            if ($nextLocalization !== false) {
                $this->currentRecord['localization'] = $this->getGoCodeByLocalizationName(
                    $nextLocalization['value']
                );
                return;   
            }
        }
        
        
        // current line done
        // read next line
        
        $line = $this->readLine();
        if ($line === false) {
            // EOF
            return;
        }
        
        /**
         * 0 => eSLDB code
         * 1 => Original Database Code
         * 2 => Experimental annotation
         * 3 => Prediction
         * 4 => Expreimental system type (computed later)
         * 
         * @var array
         */
        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 4);
        
        if ($recordArray[2] == 'None') {
            // No experimental annotation, use prediction
            $recordArray[2] = $recordArray[3];
            $recordArray[4] = 'SVM decision tree (predicted)';
        } else {
            $recordArray[4] = 'experimental';
        }
        
        $this->currentLine = array(
            'originalCode' => $recordArray[1],
            'localization' => explode(', ', $recordArray[2]),
            'experimentalSystemType' => $recordArray[4]
        );
        
        $this->currentRecord = array(
            'proteinId' => $this->currentLine['originalCode'],
            'namingConvention' => 'EnsemblPeptideId',
            'localization' => $this->getGoCodeByLocalizationName($this->currentLine['localization'][0]),
            'pubmedId' => 17108361,
            'experimentalSystemType' => $this->currentLine['experimentalSystemType']
        );

    }
}