<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser\Parser;

class Biogrid extends AbstractParser implements ParserInterface
{
    protected $matching_files = array(
        'biogrid' => 'BiogridTest',
        'BIOGRID-ALL-3.1.76.mitab.txt' => 'Biogrid'
    );
    
    public function getFieldArray($file_handle) {
        $first_line = fgets($file_handle);
        
        //strip leading #
        $header_field_filtered = substr($first_line, 1);
        
        $fields = explode("\t", $header_field_filtered);
        
        $fields = $this->cleanFieldArray($fields);
        $fields = $this->camelizeFieldArray($fields);
        
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