<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser\Parser;

class Pagosub extends AbstractParser implements ParserInterface
{
    protected $matching_files = array(
        'pagosub_ce' => 'PagosubCeTest',
        'pagosub_dm' => 'PagosubDmTest',
        'pagosub_hs' => 'PagosubHsTest',
        'caenorhabditis_elegans.csv' => 'PagosubCe',
        'drosophila_melanogaster.csv' => 'PagosubDm',
        'homo_sapiens.csv' => 'PagosubHs'
    );
    
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
        
        $fields = $this->cleanFieldArray($fields);
        $fields = $this->camelizeFieldArray($fields);
        
        return $fields;
    }
    
    public function getContentArray($file_handle) {
        //drop header
        fgets($file_handle); //header
        fgets($file_handle); //field names
        
        $records = array();
        
        //read records
        while (($line = fgets($file_handle)) !== false) {
            $line = ltrim($line);
            $records[] = explode(", ", $line);
        }
        if (!feof($file_handle)) {
            throw new \Exception("Unexpected error while reading database");
        }
        
        return $records;
    }
}