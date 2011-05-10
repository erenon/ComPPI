<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser\Parser;

class Ptarget extends AbstractParser implements ParserInterface
{
    public function isMatch($filename) {
        return ('ptarget' == substr($filename, 0, 7));
    }
    
    public function getFieldArray($file_handle) {
        /** @todo improve field names */
        $fields = array(
            'name',
            'localization',
            'weight'
        );
        
        $fields = $this->setFieldType($fields, $file_handle);
        
        return $fields;
    }
    
    public function getContentArray($file_handle) {
        $records = array();
        
        //read records
        while (($line = fgets($file_handle)) !== false) {
            $line = trim($line);
            $records[] = preg_split("/[\s]+/", $line);
        }
        if (!feof($file_handle)) {
            throw new \Exception("Unexpected error while reading database");
        }
        
        return $records;      
    }
}