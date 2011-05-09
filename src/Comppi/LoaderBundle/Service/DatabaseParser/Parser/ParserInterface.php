<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser\Parser;

interface ParserInterface
{
    public function isMatch($filename);
    public function getFieldArray($file_handle); 
    public function getContentArray($file_handle);
}