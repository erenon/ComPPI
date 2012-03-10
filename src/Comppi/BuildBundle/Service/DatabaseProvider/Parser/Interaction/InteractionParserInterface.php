<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

interface InteractionParserInterface extends \Iterator
{
    public static function canParseFilename($fileName);
    
    public function getDatabaseIdentifier();
    public function getDatabaseNamingConvention();
}