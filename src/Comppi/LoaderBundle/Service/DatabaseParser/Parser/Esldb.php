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
    
    protected $field_blacklist = array(
        'eSLDB code'
    );
    
    public function getFieldArray($file_handle) {
        $first_line = fgets($file_handle);
        $fields = explode("\t", $first_line);
        
        $fields = $this->filterFieldArray($fields);
        $fields = $this->cleanFieldArray($fields);
        $fields = $this->camelizeFieldArray($fields);
        
        return $fields;
    }
}