<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser\Parser;

class Esldb extends AbstractParser implements ParserInterface
{
    protected $matching_files = array(
        'esldb_ce' => 'EsldbCeTest',
        'esldb_hs' => 'EsldbHsTest',
        'esldb_sc' => 'ElsdbScTest',
        'eSLDB_Caenorhabditis_elegans.txt' => 'EsldbCe',
        'eSLDB_Homo_sapiens.txt' => 'EsldbHs',
        'eSLDB_Saccharomyces_cerevisiae.txt' => 'EsldbSc'
    );
    
    public function getFieldArray($file_handle) {
        $first_line = fgets($file_handle);
        $fields = explode("\t", $first_line);
        
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