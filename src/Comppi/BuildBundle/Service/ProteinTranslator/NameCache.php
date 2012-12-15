<?php

namespace Comppi\BuildBundle\Service\ProteinTranslator;

class NameCache
{
    private $cache = array();

    const SPECIE_SIZE = 4;
    const CONVENTION_SIZE = 5;
    const NAME_SIZE = 10;

    /**
     * Gets a cache entry
     *
     * @param int $specie
     * @param string $namingConvention
     * @param string $name
     * @return int|bool ComPPI id if cached or false on cache miss
     */
    public function getComppiId($specie, $namingConvention, $name) {
        if (isset($this->cache[$specie])) {
            if (isset($this->cache[$specie][$namingConvention])) {
                if (isset($this->cache[$specie][$namingConvention][$name])) {
                    return $this->cache[$specie][$namingConvention][$name];
                }
            }
        }

        return false;
    }

    /**
     * Sets a cache entry
     *
     * @param int $specie
     * @param string $namingConvention
     * @param string $name
     * @param string $comppiId
     */
    public function setComppiId($specie, $namingConvention, $name, $comppiId) {
        if (!isset($this->cache[$specie])) {
            if (count($this->cache) >= self::SPECIE_SIZE) {
                array_shift($this->cache);
            }

            $this->cache[$specie] = array();
        }

        if (!isset($this->cache[$specie][$namingConvention])) {
            if (count($this->cache[$specie]) >= self::CONVENTION_SIZE) {
                array_shift($this->cache[$specie]);
            }

            $this->cache[$specie][$namingConvention] = array();
        }

        $this->cache[$specie][$namingConvention][$name] = $comppiId;
    }
}