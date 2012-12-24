<?php

namespace Comppi\BuildBundle\Service\SpecieProvider;

class SpecieProvider
{
    private $descriptors;

    public function __construct() {
        $this->descriptors[] = new SpecieDescriptor(0, 'hs', 'Homo sapiens');
        $this->descriptors[] = new SpecieDescriptor(1, 'dm', 'Drosophila melanogaster');
        $this->descriptors[] = new SpecieDescriptor(2, 'ce', 'Caenorhabditis elegans');
        $this->descriptors[] = new SpecieDescriptor(3, 'sc', 'Saccharomyces cerevisiae');
    }

    public function getDescriptors() {
        return $this->descriptors;
    }

    public function getSpecieById($id) {
        foreach ($this->descriptors as $descriptor) {
            if ($descriptor->id == $id) {
                return $descriptor;
            }
        }

        throw new \InvalidArgumentException('No specie found for id: "' . $id . '"');
    }

    public function getSpecieByAbbreviation($abbr) {
        foreach ($this->descriptors as $descriptor) {
            if ($descriptor->abbreviation == $abbr) {
                return $descriptor;
            }
        }

        throw new \InvalidArgumentException('No specie found for abbreviation: "' . $abbr . '"');
    }

    public function getSpecieByName($name) {
        foreach ($this->descriptors as $descriptor) {
            if ($descriptor->name == $name) {
                return $descriptor;
            }
        }

        throw new \InvalidArgumentException('No specie found for name: "' . $name . '"');
    }
}