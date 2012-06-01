<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class Locate extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'LOCATE_human_v6_20081121.xml'
    );

    protected static $minimumProbability = 95;

    protected $currentProtein;
    protected $currentLocal;
    protected $proteinStack = array();
    protected $nextProtein = array();

    protected $localizationToGoCode = array (
        'centrosome' => 'GO:0005813',
        'cytoplasmic' => 'GO:0005737',
    	'cytoplasm' => 'GO:0005737',
        'cytoplasmic vesicles' => 'GO:0031982',
        'cytoskeleton' => 'GO:0005856',
        'endoplasmic reticulum' => 'GO:0005783',
        'endosomes' => 'GO:0005768',
        'early endosomes' => 'GO:0005769',
        'late endosomes' => 'GO:0005770',
        'ERGIC' => 'GO:0005793',
        'extracellular region' => 'GO:0005576',
        'Golgi apparatus' => 'GO:0005794',
        'golgi cis cisterna' => 'GO:0000137',
        'golgi trans cisterna' => 'GO:0000138',
        'golgi trans face' => 'GO:0005802',
        'medial-golgi' => 'GO:0005797',
        'lipid particles' => 'GO:0005811',
        'lysosome' => 'GO:0005764',
        'melanosome' => 'GO:0042470',
        'mitochondrion' => 'GO:0005739',
        'inner mitochondrial membrane' => 'GO:0005743',
        'outer mitochondrial membrane' => 'GO:0005741',
        'nuclear' => 'GO:0005634',
    	'nucleus' => 'GO:0005634',
        'nuclear envelope' => 'GO:0005635',
        'nuclear speckles' => 'GO:0016607',
        'nucleolus' => 'GO:0005730',
        'peroxisome' => 'GO:0005777',
        'plasma membrane' => 'GO:0005886',
        'apical plasma membrane' => 'GO:0016324',
        'basolateral plasma membrane' => 'GO:0016323',
        'secretory granule' => 'GO:0030141',
        'synaptic vesicles' => 'GO:0008021',
        'tight junction' => 'GO:0005923',
        'transport vesicle' => 'GO:0030133'
    );

    protected $hasHeader = false;

    protected $parser;

    const ST_START = 1;
    const ST_LOCATE_PROTEIN_START = 2;
    const ST_PROTEIN = 3;
    const ST_READ_NAMING_CONVENTION = 4;
    const ST_READ_NAME = 5;
    const ST_PREDICTION = 6;
    const ST_READ_METHOD = 7;
    const ST_READ_LOCATION = 8;
    const ST_READ_EVALUATION = 9;

    protected $status = self::ST_START;

    public function __construct($fileName) {
        parent::__construct($fileName);

        $this->parser = xml_parser_create();
        xml_set_element_handler($this->parser,
            array($this, "startTagHandler"),
            array($this, "endTagHandler")
        );
        xml_set_character_data_handler($this->parser,
            array($this, "cdataHandler")
        );
    }

    public function startTagHandler($parser, $name, $attribute) {
        switch ($name) {
            case 'LOCATE_PROTEIN':
                $this->status = self::ST_LOCATE_PROTEIN_START;
                break;
            case 'PROTEIN':
                $this->status = self::ST_PROTEIN;
                $this->currentProtein = array();
                break;
            case 'SOURCE_NAME':
                if ($this->status == self::ST_PROTEIN) {
                    $this->status = self::ST_READ_NAMING_CONVENTION;
                }
                break;
            case 'ACCN':
                if ($this->status == self::ST_PROTEIN) {
                    $this->status = self::ST_READ_NAME;
                }
                break;
            case 'SCL_PREDICTION':
                $this->status = self::ST_PREDICTION;
                $this->locationIndex = 0;
                $this->currentProtein['locals'] = array();
                $this->currentLocal = array();
                break;
            case 'METHOD':
                if ($this->status == self::ST_PREDICTION) {
                    $this->status = self::ST_READ_METHOD;
                }
                break;
            case 'LOCATION':
                if ($this->status == self::ST_PREDICTION) {
                    $this->status = self::ST_READ_LOCATION;
                }
                break;
            case 'EVALUATION':
                if ($this->status == self::ST_PREDICTION) {
                    $this->status = self::ST_READ_EVALUATION;
                }
                break;
        }
    }

    public function endTagHandler($parser, $name) {
        switch ($name) {
            case 'PROTEIN':
                // avoid reading external annotations
                $this->status = self::ST_PREDICTION;
                break;
            case 'LOCATE_PROTEIN':
                $this->proteinStack[] = $this->currentProtein;
                $this->status = self::ST_START;
                break;
        }
    }

    public function cdataHandler($parser, $data) {
        switch ($this->status) {
            case self::ST_READ_NAMING_CONVENTION:
                $namingConvention = $data;
                switch ($namingConvention) {
                    case 'Entrez Protein':
                        $namingConvention = 'EntrezProtein';
                        break;
                    case 'RefSeq Protein':
                        $namingConvention = 'refseq';
                        break;
                    case 'Ensembl-Peptide Human':
                        $namingConvention = 'EnsemblPeptideId';
                        break;
                }

                $this->currentProtein['namingConvention'] = $namingConvention;
                $this->status = self::ST_PROTEIN;
                break;
            case self::ST_READ_NAME:
                $this->currentProtein['proteinId'] = $data;
                $this->status = self::ST_PROTEIN;
                break;
            case self::ST_READ_METHOD:
                $this->currentLocal['experimentalSystemType'] = $data;
                $this->status = self::ST_PREDICTION;
                break;
            case self::ST_READ_LOCATION:
                $this->currentLocal['localization'] = $data;
                $this->status = self::ST_PREDICTION;
                break;
            case self::ST_READ_EVALUATION:
                if ($data > self::$minimumProbability) {
                    // add local
                    $this->currentProtein['locals'][] = $this->currentLocal;
                } else {
                    // discard local
                    $this->currentLocal = array();
                }
                $this->status = self::ST_PREDICTION;
                break;
        }
    }

    protected function readProtein() {
        while (!feof($this->fileHandle) && count($this->proteinStack) == 0) {
            $data = fread($this->fileHandle, 4096);
            if (!xml_parse($this->parser, $data, false)) {
                printf("XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($this->parser)),
                    xml_get_current_line_number($this->parser));
                echo "\n[FAIL] Failed to load Locate\n";
            }
        }

        if (count($this->proteinStack) > 0) {
            $nextLocalizedProtein = array_shift($this->proteinStack);

            $this->nextProtein = $nextLocalizedProtein;
            return true;
        } else {
            return false;
        }
    }

    protected function readRecord() {
        if (isset($this->nextProtein['locals'])) {
            $nextLocalization = each($this->nextProtein['locals']);

            if ($nextLocalization !== false) {
                $nextLocalizedProtein['proteinId'] = $this->nextProtein['proteinId'];
                $nextLocalizedProtein['namingConvention'] = $this->nextProtein['namingConvention'];
                $nextLocalizedProtein['pubmedId'] = 17986452;

                $goCode = $this->getGoCodeByLocalizationName($nextLocalization['value']['localization']);
                $nextLocalizedProtein['localization'] = $goCode;
                $nextLocalizedProtein['experimentalSystemType'] = $nextLocalization['value']['experimentalSystemType'];
                $this->currentRecord = $nextLocalizedProtein;

                return;
            }
        }

        // current protein done
        // read next
        $sucess = false;
        while ($sucess === false && !feof($this->fileHandle)) {
            $sucess = $this->readProtein();
        }

        if ($sucess) {
            return $this->readRecord();
        }
    }

    public function valid() {
        $valid = !feof($this->fileHandle);
        if (!$valid) {
            xml_parser_free($this->parser);
            fclose($this->fileHandle);
        }

        return $valid;
    }
}
