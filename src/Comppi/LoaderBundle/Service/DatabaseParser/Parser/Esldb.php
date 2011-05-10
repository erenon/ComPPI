<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser\Parser;

class Esldb extends AbstractParser implements ParserInterface
{
    public function isMatch($filename) {
        return ('esldb' == substr($filename, 0, 5));
    }
    
    public function getFieldArray($file_handle) {
        $first_line = fgets($file_handle);
        $fields = explode("\t", $first_line);
        
        $fields = $this->cleanFieldArray($fields);
        $fields = $this->setFieldType($fields, $file_handle);
        
        return $fields;
    }
    
    public function getContentArray($file_handle) {
        //drop header
        fgets($file_handle);
        
        $records = array();
        
        //read records
        while (($record = fgets($file_handle)) !== false) {
            $records[] = explode("\t", $record);
        }
        if (!feof($file_handle)) {
            throw new \Exception("Unexpected error while reading database");
        }
        
        return $records;        
    }
}