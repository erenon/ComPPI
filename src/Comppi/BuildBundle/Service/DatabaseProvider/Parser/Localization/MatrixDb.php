<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class MatrixDb extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'ECM_Protein_list.csv',
        'Secreted_Protein_list.csv',
        'Membrane_Protein_list.csv'
    );

    protected $databaseIdentifier = "MatrixDB";

    protected $headerCount = 1;

    private $localization;

    public function __construct($fileName) {
        parent::__construct($fileName);
        $this->setLocalizationByFile(basename($fileName));
    }

    private function setLocalizationByFile($fileName) {
        switch ($fileName) {
            case 'ECM_Protein_list.csv':
                $this->localization = 'GO:0031012';
                break;
            case 'Secreted_Protein_list.csv':
                $this->localization = 'GO:0016020';
                break;
            case 'Membrane_Protein_list.csv':
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
        
        $this->unfilteredEntryCount++;

        $recordArray = explode("\t", $line);
        $this->checkRecordFieldCount($recordArray, 2);
        $proteinName = trim($recordArray[0], "\"");

        $this->currentRecord = array(
            'proteinId' => $proteinName,
            'namingConvention' => 'UniProtKB-AC',
            'localization' => $this->localization,
            'pubmedId' => 19147664,
            'experimentalSystemType' => 'Experimental (experimental)'
        );
    }
}