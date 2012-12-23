<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class DroidFriedman extends AbstractDroid
{
    protected static $parsableFileNames = array(
        'FRIEDMANPERRIMON_COAP.txt',
    );

    protected $databaseIdentifier = "DroID";

    protected $pubmedColIdx = 20;
    protected $expSysTypeColIdx = 19;

    protected function formatPubmed($field) {
        return $field;
    }

    protected function formatExpSysType($field) {
        return substr($field, 0, strpos($field, ';'));
    }
}