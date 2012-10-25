<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class Ptarget extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'yeast_preds.txt'
    );

    private static $minimumProbability = 95;

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

    protected $localizationToGoCode = array(
        'cytoplasm' => 'GO:0005737',
        'Endoplasmic_Reticulum' => 'GO:0005783',
        'Extracellular/Secretory' => 'GO:secretory_pathway',
        'Golgi' => 'GO:0005794',
        'Lysosomes' => 'GO:0005764',
        'Mitochondria' => 'GO:0005739',
        'Nucleus' => 'GO:0005634',
        'Peroxysomes' => 'GO:0005777',
        'Plasma_Membrane' => 'GO:0005886',
    );

    protected $hasHeader = false;

    public function __construct($fileName) {
        parent::__construct($fileName);
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

    protected function readRecord(){
        $line = $this->readLine();
        if ($line === false) {
            // EOF
            return;
        }
        /**
         * 0 => proteinId
         * 1 => localization
         * 2 => probability
         *
         * @var array
         */
        $recordArray = preg_split('/ +/', $line);
        $this->checkRecordFieldCount($recordArray, 3);

        if (!is_numeric($recordArray[2]) || $recordArray[2] < Ptarget::$minimumProbability) {
            // invalid localization
            return $this->readRecord();
        } else {
            $this->currentRecord = array(
                'proteinId' => $recordArray[0],
                'namingConvention' => $this->getNamingConventionByName($recordArray[0]),
                'localization' => $this->getGoCodeByLocalizationName($recordArray[1]),
                'pubmedId' => 16144808,
                'experimentalSystemType' => 'domain projection method'
            );
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
}