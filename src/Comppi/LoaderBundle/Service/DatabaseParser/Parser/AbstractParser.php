<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser\Parser;

abstract class AbstractParser
{
    protected function camelize($name) {
        return
        lcfirst(
            str_replace(' ', '', 
                ucwords(
                    str_replace(array('_', '-'), ' ', $name)
                )
            )
        ); 
    }
    
    protected function cleanFieldArray(array $fields) {
        foreach ($fields as $key => $field) {
            $fields[$key] = trim($field);
        }
        
        return $fields;
    }
    
    protected function camelizeFieldArray(array $fields) {
        foreach ($fields as $key => $field) {
            $fields[$key] = $this->camelize($field);
        }
        
        return $fields;
    }
    
    public function getFieldTypeArray($file_handle) {
        //concrete parser may read header to get fields
        rewind($file_handle);
              
        $records = $this->getContentArray($file_handle);
        $max_field_length = array_fill(0, count($records[0]), 0);
        
        foreach ($records as $record) {
            foreach ($record as $key => $field) {
                if ($max_field_length[$key] < strlen($field)) {
                    $max_field_length[$key] = strlen($field);
                }
            }
        }
        
        $types = array();
        foreach ($max_field_length as $length) {
            //255: Max varchar length in MySQL <= 5.0
            if ($length <= 255) {
                /** @todo check if unicode works */
                $types[] = array('type' => 'string', 'length' => $length);
            } else {
                $types[] = array('type' => 'text');
            }
        }
        
        return $types;
    }
    
    public function getEntityName($filename) {
        return ucfirst($this->camelize($filename)); 
    }
}