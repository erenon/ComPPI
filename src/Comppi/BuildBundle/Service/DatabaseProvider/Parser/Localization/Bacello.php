<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class Bacello extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'pred_sce',
        'pred_cel',
        'pred_homo'
    );

    protected $localizationToGoCode = array (
        'Cytoplasm' => 'GO:0005737',
        'Secretory' => 'GO:secretory_pathway',
        'Mitochondrion' => 'GO:0005739',
        'Nucleus' => 'GO:0005634'
    );

    protected $hasHeader = false;

    protected function readRecord() {
        $line = $this->readLine();
        if ($line === false) {
            // EOF
            return;
        }

        // workaround empty line top of pred_homo
        if ($line == '') {
            return $this->readRecord();
        }

        $recordArray = preg_split('/ +/', $line);
        $this->checkRecordFieldCount($recordArray, 2);

        $this->currentRecord = array(
            'proteinId' => $recordArray[0],
            'namingConvention' => 'EnsemblPeptideId',
            'localization' => $this->getGoCodeByLocalizationName($recordArray[1]),
            'pubmedId' => 16873501,
            'experimentalSystemType' => 'SVM decision tree (predicted)'
        );
    }
}