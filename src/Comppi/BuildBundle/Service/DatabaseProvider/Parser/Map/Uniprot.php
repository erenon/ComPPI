<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class Uniprot extends AbstractMapParser
{
    protected static $parsableFileNames = array(
        'YEAST_559292_idmapping_selected.tab',
        'CAEEL_6239_idmapping_selected.tab',
        'DROME_7227_idmapping_selected.tab'
    );

    protected $currentLine;

    protected $fields = array(
        'UniProtKB-ID' => 1,
        'EntrezGene' => 2
    );

    protected function readRecord() {
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

        foreach ($this->fields as $convention => $fieldIndex) {
            if (isset($recordArray[$fieldIndex])) {
                $this->addToMap($maps, $convention, $recordArray[$fieldIndex]);
            }
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

    protected function addToMap(array &$map, $convention, $nameString) {
        if (empty($nameString)) {
            return;
        }

        $names = explode('; ', $nameString);

        foreach ($names as $name) {
            $map[] = array(
                'convention' => $convention,
                'name' => $name
            );
        }
    }
}