<?php

namespace Comppi\LoaderBundle\Service\EntityGenerator\Parser;

class AbstractParser
{
    protected function cleanFieldArray(array $fields) {
        foreach ($fields as $key => $field) {
            $fields[$key] = trim($field);
        }
        
        return $fields;
    }
}