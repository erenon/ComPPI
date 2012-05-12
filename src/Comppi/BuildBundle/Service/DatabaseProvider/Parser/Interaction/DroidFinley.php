<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class DroidFinley extends AbstractDroid
{
    protected static $parsableFileNames = array(
        'finley_yth.txt',
    );

    protected $pubmedColIdx = 7;
    protected $expSysTypeColIdx = 22;

    protected function formatPubmed($field) {
        return $field;
    }

    protected function formatExpSysType($field) {
        $start = strpos($field, '(') + 1;
        $end = strpos($field, ')');

        return substr($field, $start, $end-$start);
    }
}