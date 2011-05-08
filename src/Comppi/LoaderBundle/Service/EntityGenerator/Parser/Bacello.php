<?php

namespace Comppi\LoaderBundle\Service\EntityGenerator\Parser;

class Bacello extends AbstractParser implements ParserInterface
{
    public function isMatch($filename) {
        return ('bacello' == substr($filename, 0, 7));
    }
    
    public function getFieldArray($file_handle) {
        /** @todo improve field names */
        $fields = array(
            'name',
            'localization',
        );
        
        return $fields;
    }
}