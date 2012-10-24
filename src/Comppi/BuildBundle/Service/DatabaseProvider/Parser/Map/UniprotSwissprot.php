<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class UniprotSwissprot extends AbstractMapParser
{
    protected static $parsableFileNames = array(
        'hs_uniprot_sprot.dat',
        'dm_uniprot_sprot.dat',
        'ce_uniprot_sprot.dat',
        'sc_uniprot_sprot.dat'
    );

    private $specieIds = array (
        'hs_uniprot_sprot.dat' => 9606,
        'dm_uniprot_sprot.dat' => 7227,
        'ce_uniprot_sprot.dat' => 6239,
        'sc_uniprot_sprot.dat' => 4932
    );

    private $specieId;

    public function __construct($fileName) {
        parent::__construct($fileName);

        $this->specieId = $this->specieIds[basename($fileName)];
    }

    protected function readRecord() {
        $validRead = false;

        $accessionPrefix = '"AC   ';
        $suffix = ';"';

        $acPrefixLen = strlen($accessionPrefix);
        $suffixLen = strlen($suffix);

        $speciePrefix = '"OX   NCBI_TaxID=';
        $specieLine = $speciePrefix . $this->specieId . $suffix;

        $lastAccession;

        while ($validRead === false) {
            $line = $this->readLine();
            if ($line === false) {
                // EOF
                return;
            }

            if (substr($line, 0, $acPrefixLen) == $accessionPrefix) {
                $lastAccession = substr($line, $acPrefixLen, -1 * $suffixLen);
            } else if ($line == $specieLine) {
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