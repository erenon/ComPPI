<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser\Parser;

class Bacello extends AbstractParser implements ParserInterface
{
    protected $matching_files = array (
        'bacello_ce' => 'BacelloCeTest',
        'bacello_hs' => 'BacelloHsTest',
        'bacello_sc' => 'BacelloScTest',
        'pred_cel'	 => 'BacelloCe',
        'pred_homo'  => 'BacelloHs',
        'pred_sce'   => 'BacelloSc'
    );
    
    public function getFieldArray($file_handle) {
        /** @todo improve field names */
        $fields = array(
            'name',
            'localization',
        );
        
        $fields = $this->camelizeFieldArray($fields);
        return $fields;
    }
    
    public function getContentArray($file_handle) {
        $records = array();
        
        //read records
        while (($line = fgets($file_handle)) !== false) {
            $line = trim($line);
            if ($line) {
                $records[] = preg_split("/[\s]+/", $line);
            }
        }
        if (!feof($file_handle)) {
            throw new \Exception("Unexpected error while reading database");
        }
        
        return $records;
    }
}