<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class HumanProteinAtlas extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'subcellular_location.csv'
    );

    protected $currentLine;

    protected $localizationToGoCode = array (
        'Aggresome' => 'GO:0016235',
        'Cell Junctions' => 'GO:0030054',
        'Centrosome' => 'GO:0005813',
        'Cytoplasm' => 'GO:0005737',
        'Cytoskeleton (Actin filaments)' => 'GO:0015629',
        'Cytoskeleton (Intermediate filaments)' => 'GO:0045111',
        'Cytoskeleton (Microtubules)' => 'GO:0015630',
        'Endoplasmic reticulum' => 'GO:0005783',
        'Focal Adhesions' => 'GO:0005925',
        'Golgi apparatus' => 'GO:0005794',
        'Mitochondria' => 'GO:0005739',
        'Nuclear membrane' => 'GO:0005635',
        'Nucleoli' => 'GO:0005730',
        'Nucleus' => 'GO:0005634',
        'Nucleus but not nucleoli' => 'GO:0005634',
        'Plasma membrane' => 'GO:0005886',
        'Vesicles' => 'GO:0031982'
    );

    protected $headerCount = 1;

    protected function readLine() {
        $line = fgetcsv($this->fileHandle);

        // end of file
        if (!$line) {
            if (!feof($this->fileHandle)) {
                throw new \Exception("Unexpected error while reading database");
            }
            return false;
        }

        return $line;
    }

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

        $recordArray = $this->readLine();
        if ($recordArray === false) {
            // EOF
            return;
        }
        $this->checkRecordFieldCount($recordArray, 5);

        // skip if not relaiable or no localization presented
        if ($recordArray[4] === 'Non-supportive' || $recordArray[1] === '') {
            // skip this record
            return $this->readRecord();
        }

        $sysType = $recordArray[3];
        if ($sysType === 'APE') {
            $sysType = 'APE (experimental)';
        }

        $locals = explode(';', $recordArray[1]);

        $this->currentLine = array(
            'localization' => $locals
        );

        $this->currentRecord = array(
            'proteinId' => $recordArray[0],
            'namingConvention' => 'EnsemblGeneId',
            'localization' => $this->getGoCodeByLocalizationName(
                $this->currentLine['localization'][0]
            ),
            'pubmedId' => 16127175,
            'experimentalSystemType' => $sysType
        );

        next($this->currentLine['localization']);
    }
}