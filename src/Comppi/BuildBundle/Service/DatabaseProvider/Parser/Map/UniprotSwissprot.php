<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class UniprotSwissprot extends AbstractMapParser
{
    protected static $parsableFileNames = array(
        'hs_uniprot_sprot.dat.part1',
    	'hs_uniprot_sprot.dat.part2',
        'dm_uniprot_sprot.dat.part1',
    	'dm_uniprot_sprot.dat.part2',
        'ce_uniprot_sprot.dat.part1',
    	'ce_uniprot_sprot.dat.part2',
        'sc_uniprot_sprot.dat.part1',
    	'sc_uniprot_sprot.dat.part2'
    );

    private $specieIds = array (
        'hs_uniprot_sprot.dat.part1' => 9606,
    	'hs_uniprot_sprot.dat.part2' => 9606,
        'dm_uniprot_sprot.dat.part1' => 7227,
    	'dm_uniprot_sprot.dat.part2' => 7227,
        'ce_uniprot_sprot.dat.part1' => 6239,
    	'ce_uniprot_sprot.dat.part2' => 6239,
        'sc_uniprot_sprot.dat.part1' => 4932,
    	'sc_uniprot_sprot.dat.part2' => 4932
    );

    private $specieId;

    public function __construct($fileName) {
        parent::__construct($fileName);

        file_put_contents(STDERR, 'UniprotSwissprot parser is deprecated and will be removed.' .
         ' Delete all xx_uniprot_sprot.dat.partX sources as they are no longer needed.');

        $this->specieId = $this->specieIds[basename($fileName)];
    }

    protected function readRecord() {
        $validRead = false;

        $accessionPrefix = 'AC   ';
        $suffix = ';';

        $acPrefixLen = strlen($accessionPrefix);
        $suffixLen = strlen($suffix);

        $speciePrefix = 'OX   NCBI_TaxID=';
        $specieLine = $speciePrefix . $this->specieId . $suffix;

        $specieLineLen = strlen($specieLine);

        $lastAccession;

        while ($validRead === false) {
            $line = $this->readLine();
            if ($line === false) {
                // EOF
                return;
            }

            if (substr($line, 0, $acPrefixLen) == $accessionPrefix) {
                $lastAccession = substr($line, $acPrefixLen, -1 * $suffixLen);
            } else if (substr($line, 0, $specieLineLen) == $specieLine) {
                $validRead = true;
            }
        }

        $this->currentRecord = array(
            'namingConventionA' => 'UniProtKB-AC',
            'namingConventionB'	=> 'UniProtKB/Swiss-Prot',
            'proteinNameA'	=> $lastAccession,
            'proteinNameB'	=> $lastAccession
        );
    }
}