<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider;

use Symfony\Component\Finder\Finder;

class DatabaseProvider
{
    private $rootDir;

    public function __construct($databaseRootDir) {
        $this->rootDir = $databaseRootDir;
    }

    public function getMaps() {

    }
}