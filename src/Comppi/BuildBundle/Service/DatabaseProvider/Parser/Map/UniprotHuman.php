<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class UniprotHuman extends Uniprot
{
    protected static $parsableFileNames = array(
        'HUMAN_9606_idmapping_selected.tab',
    );

    protected $fields = array(
        'UniProtKB-ID' => 1,
        'EntrezGene' => 2,
        'EnsemblGeneId' => 19,
        'EnsemblPeptideId' => 21
    );
}