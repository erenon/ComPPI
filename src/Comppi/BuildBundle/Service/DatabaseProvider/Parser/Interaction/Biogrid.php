<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class Biogrid extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'BIOGRID-ORGANISM-Homo_sapiens.tab2.txt',
        'BIOGRID-ORGANISM-Drosophila_melanogaster.tab2.txt',
        'BIOGRID-ORGANISM-Saccharomyces_cerevisiae.tab2.txt',
        'BIOGRID-ORGANISM-Caenorhabditis_elegans.tab2.txt'
    );

    protected $databaseIdentifier = "BioGRID";

    protected $headerCount = 1;

    protected function readRecord() {
        $validRead = false;

        while (!$validRead && !feof($this->fileHandle)) {
            $line = $this->readLine();

            if ($line === false) {
                // EOF
                return;
            }

            $recordArray = explode("\t", $line);

            $this->checkRecordFieldCount($recordArray, 24);

            // 12: Experimental System type column index
            if ($recordArray[12] != 'genetic') {
                $validRead = true;
                $this->currentRecord = array(
                    'proteinANamingConvention' => 'EntrezGene',
                    'proteinAName' => $recordArray[1],
                	'proteinBNamingConvention' => 'EntrezGene',
                    'proteinBName' => $recordArray[2],
                    'pubmedId' => $recordArray[14],
                    'experimentalSystemType' => $recordArray[12]
                );
            } // else continue reading
        }
    }
}