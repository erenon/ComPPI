<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class DroidCuragen extends AbstractDroid
{
    protected static $parsableFileNames = array(
        'CURAGEN_YTH.txt',
    );

    protected $databaseIdentifier = "DroID";

    protected $pubmedColIdx = 7;
    protected $expSysTypeColIdx = 22;

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