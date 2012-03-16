<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

interface LocalizationParserInterface extends \Iterator
{
    public static function canParseFilename($fileName);
    
    public function getDatabaseIdentifier();
}