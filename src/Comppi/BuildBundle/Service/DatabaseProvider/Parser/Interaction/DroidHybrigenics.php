<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class DroidHybrigenics extends AbstractDroid
{
    protected static $parsableFileNames = array(
        'HYBRIGENICS_YTH.txt'
    );

    protected $pubmedColIdx = 7;
    protected $expSysTypeColIdx = 20;

    protected function formatPubmed($field) {
        // 5: strlen('PMID:')
        return substr($field, 5);
    }

    protected function formatExpSysType($field) {
        $start = strpos($field, '(') + 1;
        $end = strpos($field, ')');

        return substr($field, $start, $end-$start);
    }
}