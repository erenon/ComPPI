<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class Esldb extends AbstractLocalizationParser
{
    // This class is suitable for HS, SC, CE only, DM not supported
    // Do not add DM files here
    protected static $parsableFileNames = array(
        'eSLDB_Saccharomyces_cerevisiae.txt',
        'eSLDB_Caenorhabditis_elegans.txt',
        'eSLDB_Homo_sapiens.txt'
    );

    protected $databaseIdentifier = "eSLDB";

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
        'Secretory pathway' => 'GO:secretory_pathway',
        'Secretory' => 'GO:secretory_pathway',
        'Vacuole' => 'GO:0005773',
        'Extracellular' => 'GO:0005576',
        'Endosome' => 'GO:0005768'
    );

    protected $headerCount = 1;

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

        if ($recordArray[2] == 'None' || $recordArray[2] == '') {
            // No experimental annotation, use prediction
            if ($recordArray[3] == 'None') {
                // no prediction, skip record
                return $this->readRecord();
            }
            $recordArray[2] = $recordArray[3];
            $recordArray[4] = 'SVM decision tree (predicted)';
        } else {
            $recordArray[4] = 'experimental';
        }

        $this->currentLine = array(
            'localization' => explode(', ', $recordArray[2])
        );

        try {
            $local = $this->getGoCodeByLocalizationName($this->currentLine['localization'][0]);
        } catch (\InvalidArgumentException $e) {
            var_dump($recordArray);
            echo count($recordArray) . "\n";
        }

        $this->currentRecord = array(
            'proteinId' => $recordArray[1],
            'namingConvention' => 'EnsemblPeptideId',
            'localization' => $local,
            'pubmedId' => 17108361,
            'experimentalSystemType' => $recordArray[4]
        );

        next($this->currentLine['localization']);

    }
}