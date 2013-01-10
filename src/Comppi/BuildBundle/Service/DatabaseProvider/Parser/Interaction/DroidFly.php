<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class DroidFly extends AbstractDroid
{
    protected static $parsableFileNames = array(
        'FLY_OTHER_PHYSICAL.txt',
    );

    protected $databaseIdentifier = "DroID";

    protected $pubmedColIdx = 3;
    protected $expSysTypeColIdx = 2;

    protected function formatPubmed($field) {
        $pubmeds = explode(',', $field);

        foreach ($pubmeds as $pubmed) {
            if (is_numeric($pubmed)) {
                return $pubmed;
            }
        }

        return 0;
    }

    protected function formatExpSysType($field) {
        preg_match_all("/MI\:\d+\((.+?)\)/", $field, $matches);
        return $matches[1];
    }
}