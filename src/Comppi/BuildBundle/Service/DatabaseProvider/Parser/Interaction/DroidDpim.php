<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class DroidDpim extends AbstractDroid
{
    protected static $parsableFileNames = array(
        'DPIM_COAPCOMPLEX.txt',
    );

    protected $databaseIdentifier = "DroID";

    protected $pubmedColIdx = 4;
    protected $expSysTypeColIdx = 6;

    protected function formatPubmed($field) {
        return $field;
    }

    protected function formatExpSysType($field) {
        $start = strpos($field, '(') + 1;
        $end = strpos($field, ')');

        return substr($field, $start, $end-$start);
    }
}