<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

interface MapParserInterface extends \Iterator
{
    static function canParseFilename($filename);
    /*function getProteinNameA();
    function getProteinNameB();
    function getNamingConventionA();
    function getNamingConventionB();*/
}