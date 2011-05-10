<?php

namespace Comppi\LoaderBundle\Service\EntityGenerator;

class EntityGenerator
{
    public $template_head;
    public $template_field;
    public $template_foot;
    
    const FIELD_SEPARATOR = '{% GENERAL FIELD SEPARATOR %}';
    const PLACEHOLDER_ENTITY_NAME = '{ENTITY_NAME}';
    const PLACEHOLDER_FIELD_TYPE = '{FIELD_TYPE}';
    const PLACEHOLDER_FIELD_NAME = '{FIELD_NAME}';
    const DEFAULT_FIELD_ANNOTATION = 'type="string", length="255"';
    
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
            if (is_array($field)) {
                $field_name = $field['field_name'];
                $field_type = $this->getOrmAnnotationByType($field['field_type']);
            } else {
                $field_type = self::DEFAULT_FIELD_ANNOTATION;
                $field_name = $field;
            }
            
            /** @todo change str_replace to preg_replace, eliminate all invalid php variable char */
            $field_name = str_replace(array(' ', '-'), '_', $field_name);
            
            $filled_field = str_replace(self::PLACEHOLDER_FIELD_TYPE, $field_type, $this->template_field);
            $filled_field = str_replace(self::PLACEHOLDER_FIELD_NAME, $field_name, $filled_field);
            $output .= $filled_field;
        }
        
        //insert foot
        $output .= $this->template_foot;
        
        return $output;
    }
    
    private function getOrmAnnotationByType($type) {
        $annotations = array();
        
        foreach ($type as $key => $value) {
            $annotations[] = $key . '="' . $value . '"';
        }
        
        $annotation = join(', ', $annotations);
        
        return $annotation;
    }
}