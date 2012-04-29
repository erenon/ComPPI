<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class MatrixDb extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'ECM_Protein_list_20100825.txt',
        'Membrane_Protein_list_20100825.txt',
        'Secreted_Protein_list_20100825.txt'
    );

    protected $hasHeader = true;

    private $localization;

    public function __construct($fileName) {
        parent::__construct($fileName);
        $this->setLocalizationByFile(basename($fileName));
    }

    private function setLocalizationByFile($fileName) {
        switch ($fileName) {
            case 'ECM_Protein_list_20100825.txt':
                $this->localization = 'GO:0031012';
                break;
            case 'Membrane_Protein_list_20100825.txt':
                $this->localization = 'GO:0016020';
                break;
            case 'Secreted_Protein_list_20100825.txt':
                $this->localization = 'GO:0005576';
                break;
            default:
                throw new \InvalidArgumentException("No localization found for file: '" . $fileName . "'");
                break;
        }
    }

    protected function readRecord() {
        $line = $this->readLine();
        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 2);

        $this->currentRecord = array(
            'proteinId' => $recordArray[0],
            'namingConvention' => 'UniProtKB-AC',
            'localization' => $this->localization,
            'pubmedId' => 19147664,
            'experimentalSystemType' => 'Experimental (experimental)'
        );
    }
}