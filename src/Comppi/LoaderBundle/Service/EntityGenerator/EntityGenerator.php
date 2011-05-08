<?php

namespace Comppi\LoaderBundle\Service\EntityGenerator;

class EntityGenerator
{
    public $template_head;
    public $template_field;
    public $template_foot;
    
    const FIELD_SEPARATOR = '{% GENERAL FIELD SEPARATOR %}';
    const PLACEHOLDER_ENTITY_NAME = '{ENTITY_NAME}';
    const PLACEHOLDER_FIELD_NAME = '{FIELD_NAME}';
    
    public function __construct($template_file) {
        //get template pieces
        $template_raw = file_get_contents($template_file);
        $template_parts = explode(self::FIELD_SEPARATOR, $template_raw);
        
        if (count($template_parts) < 3) {
            throw new \UnexpectedValueException("Malformed template file");
        }
        
        $this->template_head = $template_parts[0];
        $this->template_field = $template_parts[1];
        $this->template_foot = $template_parts[2];        
    }
    
    public function generate($name, array $fields) {
        $name = ucfirst($name);
        
        //insert head
        $output = str_replace(self::PLACEHOLDER_ENTITY_NAME, $name, $this->template_head);
        
        //insert fields
        foreach ($fields as $field) {
            /** @todo change str_replace to preg_replace, eliminate all invalid php variable char */
            $field = str_replace(array(' ', '-'), '_', $field);
            $output .= str_replace(self::PLACEHOLDER_FIELD_NAME, $field, $this->template_field);
        }
        
        //insert foot
        $output .= $this->template_foot;
        
        return $output;
    }
}