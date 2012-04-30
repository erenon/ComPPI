<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class EsldbDm extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'Drosophila_melanogaster.BDGP5.4.49.pep.all.fa_subloc',
    );

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
        'Secretory pathway' => 'secretory_pathway',
        'Secretory' => 'secretory_pathway',
        'Vacuole' => 'GO:0005773',
        'Extracellular' => 'GO:0005576',
        'Endosome' => 'GO:0005768'
    );

    protected $hasHeader = false;

    protected function readRecord() {
        $line = $this->readLine();
        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = explode("\t", $line);

        $proteinId = substr($recordArray[0], 0, strpos($recordArray[0], ' '));
        $local = $this->getGoCodeByLocalizationName($recordArray[1]);

        $this->currentRecord = array(
            'proteinId' => $proteinId,
            'namingConvention' => 'EnsemblPeptideId',
            'localization' => $local,
            'pubmedId' => 17108361,
            'experimentalSystemType' => 'not available'
        );
    }
}