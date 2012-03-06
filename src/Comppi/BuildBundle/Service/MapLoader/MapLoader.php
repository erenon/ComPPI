<?php

namespace Comppi\BuildBundle\Service\MapLoader;

class MapLoader
{
    private $em;

    public function __construct($em) {
        $this->em = $em;
    }
}