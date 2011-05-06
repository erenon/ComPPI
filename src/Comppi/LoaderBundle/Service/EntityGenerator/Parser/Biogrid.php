<?php

namespace Comppi\LoaderBundle\Service\EntityGenerator\Parser;

class Biogrid implements ParserInterface
{
    public function isMatch($filename) {
        return ('biogrid' == $filename);
    }
    
    public function getFieldArray($file_handle) {
        $first_line = fgets($file_handle);
        
        //strip leading #
        $header_field_filtered = substr($first_line, 1);
        
        $fields = explode("\t", $header_field_filtered);
        
        return $fields;
    }
}