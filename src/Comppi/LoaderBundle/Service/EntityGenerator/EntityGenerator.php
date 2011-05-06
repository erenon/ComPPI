<?php

namespace Comppi\LoaderBundle\Service\EntityGenerator;

class EntityGenerator
{
    public $template_head;
    public $template_field;
    public $template_foot;
    
    public function __construct() {
        //get template pieces
        $template_raw = file_get_contents(__DIR__ . '/Entity.tpl');
        $template_pieces = explode('{% GENERAL FIELD SEPARATOR %}', $template_raw);
        
        $this->template_head = $template_pieces[0];
        $this->template_field = $template_pieces[1];
        $this->output_foot = $template_pieces[2];        
    }
    
    public function generate($name, array $fields) {
        $name = ucfirst($name);
        
        //insert head
        $output = str_replace('{ENTITY_NAME}', $name, $this->template_head);
        
        //insert fields
        foreach ($fields as $field) {
            /** @todo change str_replace to preg_replace, eliminate all invalid php variable char */
            $field = str_replace(array(' ', '-'), '_', $field);
            $output .= str_replace('{FIELD_NAME}', $field, $this->template_field);
        }
        
        //insert foot
        $output .= $this->template_foot;
        
        return $output;
    }
}