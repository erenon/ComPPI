<?php

namespace Comppi\LoaderBundle\Entity;

/**
 * @orm:Entity
 */
class EsldbHs
{
    /**
     * @orm:Id
     * @orm:Column(type="integer")
     * @orm:GeneratedValue(strategy="AUTO")
     */
    protected $id;

    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $eSLDBCode;
    
    /**
     * @orm:Column(type="string", length="15")
     */
    protected $originalDatabaseCode;
    
    /**
     * @orm:Column(type="string", length="4")
     */
    protected $experimentalAnnotation;
    
    /**
     * @orm:Column(type="string", length="4")
     */
    protected $swissProtFulltextAnnotation;
    
    /**
     * @orm:Column(type="string", length="4")
     */
    protected $swissProtEntry;
    
    /**
     * @orm:Column(type="string", length="13")
     */
    protected $similarityBasedAnnotation;
    
    /**
     * @orm:Column(type="string", length="10")
     */
    protected $swissProtHomologue;
    
    /**
     * @orm:Column(type="string", length="4")
     */
    protected $eValue;
    
    /**
     * @orm:Column(type="string", length="13")
     */
    protected $prediction;
    
    /**
     * @orm:Column(type="text")
     */
    protected $aminoacidicSequence;
    
    /**
     * @orm:Column(type="string", length="5")
     */
    protected $commonMame;
    

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
     * @return string $eSLDBCode
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
     * @return string $originalDatabaseCode
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
     * @return string $experimentalAnnotation
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
     * @return string $swissProtFulltextAnnotation
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
     * @return string $swissProtEntry
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
     * @return string $similarityBasedAnnotation
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
     * @return string $swissProtHomologue
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
     * @return string $eValue
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
     * @return string $prediction
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
     * @return text $aminoacidicSequence
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
     * @return string $commonMame
     */
    public function getCommonMame()
    {
        return $this->commonMame;
    }
}