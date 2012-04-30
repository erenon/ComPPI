<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class PagosubSc extends Pagosub
{
    protected static $parsableFileNames = array(
        'saccharomyces_cerevisiae.csv'
    );

    protected $columnToLocalization = array(
        2 => 'GO:0005794',
        4 => 'GO:0005634',
        6 => 'GO:0005576',
        8 => 'GO:0005739',
        18 => 'GO:0005783',
        10 => 'GO:0005737',
        12 => 'GO:0005886',
        16 => 'GO:0005777',
        14 => 'GO:0005773'
    );
}