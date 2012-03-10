<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

interface MapParserInterface extends \Iterator
{
    static function canParseFilename($fileName);
    /*function getProteinNameA();
    function getProteinNameB();
    function getNamingConventionA();
    function getNamingConventionB();*/
}