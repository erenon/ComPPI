<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class HumanProteinpedia extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'HUPA_Localization_Data.txt'
    );

    protected $databaseIdentifier = "Human Proteinpedia";

    protected $localizationToGoCode = array (
        'Actin cytoskeleton' => 'GO:0015629',
        'Centrosome' => 'GO:0005813',
        'Cytoplasm' => 'GO:0005737',
        'Cytoskeleton' => 'GO:0005856',
        'Endoplasmic reticulum' => 'GO:0005783',
        'Endosome' => 'GO:0005768',
        'Golgi apparatus' => 'GO:0005794',
        'Lysosome' => 'GO:0005764',
        'Microtubule' => 'GO:0005874',
        'Mitochondrion' => 'GO:0005739',
        'Nucleolus' => 'GO:0005730',
        'Nucleus' => 'GO:0005634',
        'Peroxisome' => 'GO:0005777',
        'Plasma membrane' => 'GO:0005886'
    );

    protected $headerCount = 1;

    protected function readRecord() {
        $line = $this->readLine();
        if ($line === false) {
            // EOF
            return;
        }
        
        $this->unfilteredEntryCount++;

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 6);

        $this->currentRecord = array(
            'proteinId' => $recordArray[1],
            'namingConvention' => 'Hprd',
            'localization' => $this->getGoCodeByLocalizationName($recordArray[3]),
            'pubmedId' => 19718509,
            'experimentalSystemType' => 'Experiment Type (experimental)'
        );
    }
}
