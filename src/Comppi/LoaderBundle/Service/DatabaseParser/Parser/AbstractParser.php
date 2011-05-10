<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser\Parser;

abstract class AbstractParser
{
    protected function cleanFieldArray(array $fields) {
        foreach ($fields as $key => $field) {
            $fields[$key] = trim($field);
        }
        
        return $fields;
    }
    
    protected function setFieldType(array $fields, $file_handle) {
        //concrete parser may read header to get fields
        rewind($file_handle);
        
        //init max array
        $max_field_length = array();
        $i = 0;
        foreach ($fields as $field) {
            $max_field_length[$i] = 0;
            $i++;    
        }
        
        $records = $this->getContentArray($file_handle);
        
        foreach ($records as $record) {
            foreach ($record as $key => $field) {
                if ($max_field_length[$key] < strlen($field)) {
                    $max_field_length[$key] = strlen($field);
                }
            }
        }
        
        $i = 0;
        foreach ($fields as $key => $field) {
            //255: Default varchar length. see: EntityGenerator.php
            if ($max_field_length[$i] > 255) {
                $field_name = $field;
                $field_type = array('type' => 'text');
                
                $fields[$key] = array (
                    'field_name' => $field_name,
                    'field_type' => $field_type
                );
            }
            $i++;    
        }
        
        return $fields;
    }
}