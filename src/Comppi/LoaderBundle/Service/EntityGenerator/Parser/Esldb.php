<?php

namespace Comppi\LoaderBundle\Service\EntityGenerator\Parser;

class Esldb implements ParserInterface
{
    public function isMatch($filename) {
        return ('esldb' == substr($filename, 0, 5));
    }
    
    public function getFieldArray($file_handle) {
        $first_line = fgets($file_handle);
        $fields = explode("\t", $first_line);
        
        return $fields;
    }
}