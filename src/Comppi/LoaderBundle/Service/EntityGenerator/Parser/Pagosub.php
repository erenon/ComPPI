<?php

namespace Comppi\LoaderBundle\Service\EntityGenerator\Parser;

class Pagosub implements ParserInterface
{
    public function isMatch($filename) {
        return ('pagosub' == substr($filename, 0, 7));
    }
    
    public function getFieldArray($file_handle) {
        //drop first useless line
        fgets($file_handle);
        
        $header_line = fgets($file_handle);
        
        $fields = explode(", ", $header_line);
        
        foreach ($fields as $key => $field) {
            if ($brace_pos = strpos($field, ' (')) {
                //fieldname contain braces at the end, strip them
                $fields[$key] = substr($field, 0, $brace_pos); 
            }
        }
        
        return $fields;
    }
}