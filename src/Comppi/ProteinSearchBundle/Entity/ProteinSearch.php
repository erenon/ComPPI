<?php
// src/Comppi/ProteinSearchBundle/Entity/ProteinSearch.php

//use Symfony\Component\Validator\Mapping\ClassMetadata;
//use Symfony\Component\Validator\Constraints\NotBlank;
//use Symfony\Component\Validator\Constraints\Email;
//use Symfony\Component\Validator\Constraints\MinLength;
//use Symfony\Component\Validator\Constraints\MaxLength;

namespace Comppi\ProteinSearchBundle\Entity;

class ProteinSearch
{
    protected $protein_names;
    protected $species;
    protected $majorloc;

    public function getProteinNames()
    {
        return $this->protein_names;
    }

    public function ProteinNames($protein_names)
    {
        $this->protein_names = $protein_names;
    }

    public function getSpecies()
    {
        return $this->species;
    }

    public function setSpecies($species)
    {
        $this->species = $species;
    }

    public function getMajorloc()
    {
        return $this->majorloc;
    }

    public function setMajorloc($majorloc)
    {
        $this->majorloc = $majorloc;
    }
}