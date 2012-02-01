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
    
    public function next() {
        do {
            $record = fgets($this->file_handle);
            
            // end of file
            if (!$record) {
                if (!feof($this->file_handle)) {
                    throw new \Exception("Unexpected error while reading database");
                }
                return;
            }
            
            $record = trim($record);
            $record = preg_split("/[\s]+/", $record);
        } while ($this->isRecordFiltered($record));

        $record = $this->filterRecordArray($record);
        
        $this->current_line = $record; 
        $this->current_index++;
    }
}