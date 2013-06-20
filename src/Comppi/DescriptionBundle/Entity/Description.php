<?php
// src/Comppi/DescriptionBundle/Entity/Description.php

namespace Comppi\DescriptionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="descriptions")
 */
class Description
{
	/**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
	protected $description_id;
	
	/**
     * @ORM\Column(type="string")
     */
	protected $title;
	
	/**
     * @ORM\Column(type="string", length=250)
     */
	protected $url_alias;
	
	/**
     * @ORM\Column(type="datetime")
     */
	protected $last_modified;
	
	/**
     * @ORM\Column(type="smallint")
     */
	protected $public;
	
	/**
     * @ORM\Column(type="text")
     */
	protected $text;
	

    /**
     * Get description_id
     *
     * @return integer 
     */
    public function getDescriptionId()
    {
        return $this->description_id;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set url_alias
     *
     * @param string $urlAlias
     */
    public function setUrlAlias($urlAlias)
    {
        $this->url_alias = $urlAlias;
    }

    /**
     * Get url_alias
     *
     * @return string 
     */
    public function getUrlAlias()
    {
        return $this->url_alias;
    }

    /**
     * Set last_modified
     *
     * @param datetime $lastModified
     */
    public function setLastModified($lastModified)
    {
        $this->last_modified = $lastModified;
    }

    /**
     * Get last_modified
     *
     * @return datetime 
     */
    public function getLastModified()
    {
        return $this->last_modified;
    }

    /**
     * Set public
     *
     * @param smallint $public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    /**
     * Get public
     *
     * @return smallint 
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * Set text
     *
     * @param text $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Get text
     *
     * @return text 
     */
    public function getText()
    {
        return $this->text;
    }
}
