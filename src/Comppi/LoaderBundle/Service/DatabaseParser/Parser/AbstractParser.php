<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser\Parser;

abstract class AbstractParser implements \Iterator
{
    protected $matching_files = array();
    protected $field_blacklist = array();
    protected $field_blacklist_indices = array();
    
    // iterator fields
    protected $file_handle;
    protected $current_line;
    protected $current_index;
    
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
    
    public function isMatch($filename) {
        return array_key_exists($filename, $this->matching_files);
    }
    
    protected function filterFieldArray(array $fields) {
        $passedFields = array();
        $i = 0;
        foreach ($fields as $field) {
            if (!in_array($field, $this->field_blacklist)) {
                $passedFields[] = $field;
            } else {
                $this->field_blacklist_indices[] = $i;
            }
            
            $i++;
        }
        
        return $passedFields;
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
        $iterator = $this->getRecordIterator($file_handle);
        
        // get field count
        $iterator->rewind();
        if ($iterator->valid()) {
            $max_field_length = array_fill(0, count($iterator->current()), 0);
        } else {
            throw new \Exception("Invalid iterator");
        }
        
        foreach ($iterator as $record) {
            foreach ($record as $key => $field) {
                if (!isset($max_field_length[$key])) {
                    throw new \Exception(join(', ', $record));
                }
                
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
    
    protected function filterRecordArray(array $record) {
        $passedFields = array();
        
        foreach ($record as $index => $field) {
            if (!in_array($index, $this->field_blacklist_indices)) {
                $passedFields[] = $field;
            }
        }
        
        return $passedFields;
    }
    
    public function getEntityName($filename) {
        if (array_key_exists($filename, $this->matching_files)) {
            return $this->matching_files[$filename];
        }
        
        /** @todo should log here */
        return ucfirst($this->camelize($filename)); 
    }
    
    public function getRecordIterator($file_handle) {
        $this->file_handle = $file_handle;
        return $this;
    }
    
    protected function isRecordFiltered(array $record) {    
        return false;
    }
    
    /* Iterator methods */
    
    public function rewind() {
        rewind($this->file_handle);
        
        // drop header
        fgets($this->file_handle);
        
        $this->next();
    }
    
    public function current() {
        return $this->current_line;
    }
    
    public function key() {
        return $this->current_index;
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
            
            $record = explode("\t", $record);
        } while ($this->isRecordFiltered($record));

        $record = $this->filterRecordArray($record);
        
        $this->current_line = $record; 
        $this->current_index++;
    }
    
    public function valid() {
        return !feof($this->file_handle);
    }
}