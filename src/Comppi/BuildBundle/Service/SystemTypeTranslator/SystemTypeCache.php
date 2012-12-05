<?php

namespace Comppi\BuildBundle\Service\SystemTypeTranslator;

class SystemTypeCache
{
    private $cache = array();

    const SIZE = 20;

    public function getSystemTypeId($systemTypeName) {
        if (isset($this->cache[$systemTypeName])) {
            return $this->cache[$systemTypeName];
        }

        return false;
    }

    public function setSystemTypeId($systemTypeName, $id) {
        if (count($this->cache) >= self::SIZE) {
            array_shift($this->cache);
        }

        $this->cache[$systemTypeName] = $id;
    }
}