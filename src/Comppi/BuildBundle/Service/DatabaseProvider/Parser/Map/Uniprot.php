<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class Uniprot extends AbstractMapParser
{
    protected static $parsableFileNames = array(
        'YEAST_559292_idmapping_selected.tab',
        'CAEEL_6239_idmapping_selected.tab',
        'HUMAN_9606_idmapping_selected.tab',
        'DROME_7227_idmapping_selected.tab'
    );

    private $currentLine;

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

        $maps[] = array(
            'convention' => 'UniProtKB-ID',
            'name' => $recordArray[1]
        );

        $maps[] = array(
            'convention' => 'EntrezGene',
            'name' => $recordArray[2]
        );

        if ($this->fileName == 'HUMAN_9606_idmapping_selected.tab') {
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
}