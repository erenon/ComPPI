<?php

namespace Comppi\LoaderBundle\Service\EntityGenerator\Parser;

interface ParserInterface
{
    public function isMatch($filename);
    public function getFieldArray($file_handle); 
}