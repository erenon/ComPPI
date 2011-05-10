<?php

namespace Comppi\LoaderBundle\Entity;

/**
 * @orm:Entity
 */
class BacelloCe
{
    /**
     * @orm:Id
     * @orm:Column(type="integer")
     * @orm:GeneratedValue(strategy="AUTO")
     */
    protected $id;

    
    /**
     * @orm:Column(type="string", length="10")
     */
    protected $name;
    
    /**
     * @orm:Column(type="string", length="9")
     */
    protected $localization;
    

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set localization
     *
     * @param string $localization
     */
    public function setLocalization($localization)
    {
        $this->localization = $localization;
    }

    /**
     * Get localization
     *
     * @return string $localization
     */
    public function getLocalization()
    {
        return $this->localization;
    }
}