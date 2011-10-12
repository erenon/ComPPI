<?php

namespace Comppi\LoaderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class EsldbCeTest
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    
    /**
     * @ORM\Column(type="string", length="11")
     */
    protected $eSLDBCode;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $originalDatabaseCode;
    
    /**
     * @ORM\Column(type="string", length="4")
     */
    protected $experimentalAnnotation;
    
    /**
     * @ORM\Column(type="string", length="4")
     */
    protected $swissProtFulltextAnnotation;
    
    /**
     * @ORM\Column(type="string", length="4")
     */
    protected $swissProtEntry;
    
    /**
     * @ORM\Column(type="string", length="9")
     */
    protected $similarityBasedAnnotation;
    
    /**
     * @ORM\Column(type="string", length="11")
     */
    protected $swissProtHomologue;
    
    /**
     * @ORM\Column(type="string", length="5")
     */
    protected $eValue;
    
    /**
     * @ORM\Column(type="string", length="13")
     */
    protected $prediction;
    
    /**
     * @ORM\Column(type="text")
     */
    protected $aminoacidicSequence;
    
    /**
     * @ORM\Column(type="string", length="5")
     */
    protected $commonMame;
    

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set eSLDBCode
     *
     * @param string $eSLDBCode
     */
    public function setESLDBCode($eSLDBCode)
    {
        $this->eSLDBCode = $eSLDBCode;
    }

    /**
     * Get eSLDBCode
     *
     * @return string 
     */
    public function getESLDBCode()
    {
        return $this->eSLDBCode;
    }

    /**
     * Set originalDatabaseCode
     *
     * @param string $originalDatabaseCode
     */
    public function setOriginalDatabaseCode($originalDatabaseCode)
    {
        $this->originalDatabaseCode = $originalDatabaseCode;
    }

    /**
     * Get originalDatabaseCode
     *
     * @return string 
     */
    public function getOriginalDatabaseCode()
    {
        return $this->originalDatabaseCode;
    }

    /**
     * Set experimentalAnnotation
     *
     * @param string $experimentalAnnotation
     */
    public function setExperimentalAnnotation($experimentalAnnotation)
    {
        $this->experimentalAnnotation = $experimentalAnnotation;
    }

    /**
     * Get experimentalAnnotation
     *
     * @return string 
     */
    public function getExperimentalAnnotation()
    {
        return $this->experimentalAnnotation;
    }

    /**
     * Set swissProtFulltextAnnotation
     *
     * @param string $swissProtFulltextAnnotation
     */
    public function setSwissProtFulltextAnnotation($swissProtFulltextAnnotation)
    {
        $this->swissProtFulltextAnnotation = $swissProtFulltextAnnotation;
    }

    /**
     * Get swissProtFulltextAnnotation
     *
     * @return string 
     */
    public function getSwissProtFulltextAnnotation()
    {
        return $this->swissProtFulltextAnnotation;
    }

    /**
     * Set swissProtEntry
     *
     * @param string $swissProtEntry
     */
    public function setSwissProtEntry($swissProtEntry)
    {
        $this->swissProtEntry = $swissProtEntry;
    }

    /**
     * Get swissProtEntry
     *
     * @return string 
     */
    public function getSwissProtEntry()
    {
        return $this->swissProtEntry;
    }

    /**
     * Set similarityBasedAnnotation
     *
     * @param string $similarityBasedAnnotation
     */
    public function setSimilarityBasedAnnotation($similarityBasedAnnotation)
    {
        $this->similarityBasedAnnotation = $similarityBasedAnnotation;
    }

    /**
     * Get similarityBasedAnnotation
     *
     * @return string 
     */
    public function getSimilarityBasedAnnotation()
    {
        return $this->similarityBasedAnnotation;
    }

    /**
     * Set swissProtHomologue
     *
     * @param string $swissProtHomologue
     */
    public function setSwissProtHomologue($swissProtHomologue)
    {
        $this->swissProtHomologue = $swissProtHomologue;
    }

    /**
     * Get swissProtHomologue
     *
     * @return string 
     */
    public function getSwissProtHomologue()
    {
        return $this->swissProtHomologue;
    }

    /**
     * Set eValue
     *
     * @param string $eValue
     */
    public function setEValue($eValue)
    {
        $this->eValue = $eValue;
    }

    /**
     * Get eValue
     *
     * @return string 
     */
    public function getEValue()
    {
        return $this->eValue;
    }

    /**
     * Set prediction
     *
     * @param string $prediction
     */
    public function setPrediction($prediction)
    {
        $this->prediction = $prediction;
    }

    /**
     * Get prediction
     *
     * @return string 
     */
    public function getPrediction()
    {
        return $this->prediction;
    }

    /**
     * Set aminoacidicSequence
     *
     * @param text $aminoacidicSequence
     */
    public function setAminoacidicSequence($aminoacidicSequence)
    {
        $this->aminoacidicSequence = $aminoacidicSequence;
    }

    /**
     * Get aminoacidicSequence
     *
     * @return text 
     */
    public function getAminoacidicSequence()
    {
        return $this->aminoacidicSequence;
    }

    /**
     * Set commonMame
     *
     * @param string $commonMame
     */
    public function setCommonMame($commonMame)
    {
        $this->commonMame = $commonMame;
    }

    /**
     * Get commonMame
     *
     * @return string 
     */
    public function getCommonMame()
    {
        return $this->commonMame;
    }
}