<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class Uniprot implements MapParserInterface
{
    private $fileName;
    private $fileHandle = null;
    private $currentIdx;
    private $currentRecord;

    public function __construct($fileName) {
        $this->fileName = $fileName;
    }

    static function canParseFilename($fileName) {
        $parsable = array(
            'YEAST_559292_idmapping_selected.tab',
            'CAEEL_6239_idmapping_selected.tab',
            'HUMAN_9606_idmapping_selected.tab',
            'DROME_7227_idmapping_selected.tab'
        );

        return in_array($fileName, $parsable);
    }

    private $currentLine;

    private function readline() {
        $line = fgets($this->fileHandle);

        // end of file
        if (!$line) {
            if (!feof($this->fileHandle)) {
                throw new \Exception("Unexpected error while reading database");
            }
            return false;
        }

        // trim EOL
        $line = trim($line);

        // don't feed empty lines
        if ($line == "") {
            return $this->readLine();
        }

        return $line;
    }

    private function readRecord() {
        if (isset($this->currentLine['maps'])) {
            // advance cursor
            $nextMap = each($this->currentLine['maps']);

            if ($nextMap !== false) {
                $this->currentRecord['namingConventionA'] = $nextMap['value']['convention'];
                $this->currentRecord['proteinNameA'] = $nextMap['value']['name'];

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
        $recordArray = explode("\t", $line);

        $maps = array();

        $maps[] = array(
            'convention' => 'UniProtKB-ID',
            'name' => $recordArray[1]
        );

        $maps[] = array(
            'convention' => 'EntrezGene',
            'name' => $recordArray[2]
        );

        $names = explode('; ', $recordArray[19]);

        foreach ($names as $name) {
            $maps[] = array(
                'convention' => 'EnsemblGeneId',
                'name' => $name
            );
        }

        $names = explode('; ', $recordArray[21]);

        foreach ($names as $name) {
            $maps[] = array(
                'convention' => 'EnsemblPeptideId',
                'name' => $name
            );
        }

        $this->currentLine = array(
            'maps' => $maps
        );

        $this->currentRecord = array(
            'namingConventionA' => $this->currentLine['maps'][0]['convention'],
            'namingConventionB'	=> 'UniProtKB-AC',
            'proteinNameA'	=> $this->currentLine['maps'][0]['name'],
            'proteinNameB'	=> $recordArray[0]
        );

        next($this->currentLine['maps']);
    }

    /* Iterator methods */

    public function rewind() {
        if ($this->fileHandle == null) {
            $this->fileHandle = fopen($this->fileName, 'r');
            $this->currentIdx = -1;
        } else {
            rewind($this->fileHandle);
        }

        $this->readRecord();
    }

    public function current() {
        return $this->currentRecord;
    }

    public function key() {
        return $this->currentIdx;
    }

    public function next() {
        $this->currentIdx++;
        $this->readRecord();
    }

    public function valid() {
        $valid = !feof($this->fileHandle);
        if (!$valid) {
            fclose($this->fileHandle);
        }

        return $valid;
    }
}