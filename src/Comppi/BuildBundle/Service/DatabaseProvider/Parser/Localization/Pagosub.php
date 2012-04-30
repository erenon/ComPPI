<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class Pagosub extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'caenorhabditis_elegans.csv',
        'drosophila_melanogaster.csv',
        'homo_sapiens.csv'
    );

    protected static $minimumProbability = 0.95;

    protected $currentLine;

    // has double header
    protected $hasHeader = false;

    protected $columnToLocalization = array(
        2 => 'GO:0005794',
        4 => 'GO:0005634',
        6 => 'GO:0005576',
        8 => 'GO:0005739',
        14=> 'GO:0005764',
        18 => 'GO:0005783',
        10 => 'GO:0005737',
        12 => 'GO:0005886',
        16 => 'GO:0005777',
    );

    protected function readRecord() {
        if (isset($this->currentLine['localization'])) {
            // advance cursor
            $nextLocalization = each($this->currentLine['localization']);

            if ($nextLocalization !== false) {
                $this->currentRecord['localization'] = $nextLocalization['value'];
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

        $recordArray = explode(", ", $line);
        $this->checkRecordFieldCount($recordArray, 46);

        $annotationParts = explode("|", $recordArray[1]);
        $annotation = $annotationParts[1];

        $locals = array();
        foreach ($this->columnToLocalization as $col => $loc) {
            if (is_numeric($recordArray[$col]) && $recordArray[$col] > static::$minimumProbability) {
                $locals[] = $loc;
            }
        }

        if (count($locals) > 0) {
            $this->currentLine = array(
                'localization' => $locals
            );

            $this->currentRecord = array(
                'proteinId' => $annotation,
                'namingConvention' => 'UniProtKB-AC',
                'localization' => $this->currentLine['localization'][0],
                'pubmedId' => 15608166,
                'experimentalSystemType' => 'PA ML algorythm (predicted)'
            );

            next($this->currentLine['localization']);
        } else {
            // no valid localization found, read next line
            $this->readRecord();
        }
    }

    /**
     * Redefine rewind, must drop double header
     * (non-PHPdoc)
     * @see Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization.AbstractLocalizationParser::rewind()
     */
    public function rewind() {
        if ($this->fileHandle == null) {
            $this->fileHandle = fopen($this->fileName, 'r');
            $this->currentIdx = 0;
        } else {
            rewind($this->fileHandle);
        }

        // drop double header
        fgets($this->fileHandle);
        fgets($this->fileHandle);

        $this->readRecord();
    }
}