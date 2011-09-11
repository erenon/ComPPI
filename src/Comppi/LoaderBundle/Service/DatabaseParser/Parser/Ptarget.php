<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser\Parser;

class Ptarget extends AbstractParser implements ParserInterface
{
    protected $matching_files = array(
        'ptarget_ce' => 'PtargetCeTest',
        'ptarget_dm' => 'PtargetDmTest',
        'ptarget_hs' => 'PtargetHsTest',
        'ptarget_sc' => 'PtargetScTest',
        'nematode_preds.txt' => 'PtargetCe',
        'drosophila_preds.txt' => 'PtargetDm',
        'human_preds.txt' => 'PtargetHs',
        'yeast_preds.txt' => 'PtargetSc'
    );
    
    public function getFieldArray($file_handle) {
        /** @todo improve field names */
        $fields = array(
            'name',
            'localization',
            'weight'
        );
        
        $fields = $this->camelizeFieldArray($fields);
        
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