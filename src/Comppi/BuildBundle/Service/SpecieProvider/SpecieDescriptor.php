<?php

namespace Comppi\BuildBundle\Service\SpecieProvider;

class SpecieDescriptor
{
    public $id;
    public $abbreviation;
    public $name;

    public function __construct($id, $abbreviation, $name) {
        $this->id = $id;
        $this->abbreviation = $abbreviation;
        $this->name = $name;
    }
}