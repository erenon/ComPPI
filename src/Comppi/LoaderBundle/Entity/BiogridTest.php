<?php

namespace Comppi\LoaderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class BiogridTest
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    
    /**
     * @ORM\Column(type="string", length="39")
     */
    protected $iDInteractorA;
    
    /**
     * @ORM\Column(type="string", length="39")
     */
    protected $iDInteractorB;
    
    /**
     * @ORM\Column(type="string", length="63")
     */
    protected $altIDsInteractorA;
    
    /**
     * @ORM\Column(type="string", length="28")
     */
    protected $altIDsInteractorB;
    
    /**
     * @ORM\Column(type="text")
     */
    protected $aliasesInteractorA;
    
    /**
     * @ORM\Column(type="text")
     */
    protected $aliasesInteractorB;
    
    /**
     * @ORM\Column(type="string", length="28")
     */
    protected $interactionDetectionMethod;
    
    /**
     * @ORM\Column(type="string", length="24")
     */
    protected $publication1stAuthor;
    
    /**
     * @ORM\Column(type="string", length="15")
     */
    protected $publicationIdentifiers;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $taxidInteractorA;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $taxidInteractorB;
    
    /**
     * @ORM\Column(type="string", length="36")
     */
    protected $interactionTypes;
    
    /**
     * @ORM\Column(type="string", length="22")
     */
    protected $sourceDatabase;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $interactionIdentifiers;
    
    /**
     * @ORM\Column(type="string", length="2")
     */
    protected $confidenceValues;
    

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
     * Set iDInteractorA
     *
     * @param string $iDInteractorA
     */
    public function setIDInteractorA($iDInteractorA)
    {
        $this->iDInteractorA = $iDInteractorA;
    }

    /**
     * Get iDInteractorA
     *
     * @return string 
     */
    public function getIDInteractorA()
    {
        return $this->iDInteractorA;
    }

    /**
     * Set iDInteractorB
     *
     * @param string $iDInteractorB
     */
    public function setIDInteractorB($iDInteractorB)
    {
        $this->iDInteractorB = $iDInteractorB;
    }

    /**
     * Get iDInteractorB
     *
     * @return string 
     */
    public function getIDInteractorB()
    {
        return $this->iDInteractorB;
    }

    /**
     * Set altIDsInteractorA
     *
     * @param string $altIDsInteractorA
     */
    public function setAltIDsInteractorA($altIDsInteractorA)
    {
        $this->altIDsInteractorA = $altIDsInteractorA;
    }

    /**
     * Get altIDsInteractorA
     *
     * @return string 
     */
    public function getAltIDsInteractorA()
    {
        return $this->altIDsInteractorA;
    }

    /**
     * Set altIDsInteractorB
     *
     * @param string $altIDsInteractorB
     */
    public function setAltIDsInteractorB($altIDsInteractorB)
    {
        $this->altIDsInteractorB = $altIDsInteractorB;
    }

    /**
     * Get altIDsInteractorB
     *
     * @return string 
     */
    public function getAltIDsInteractorB()
    {
        return $this->altIDsInteractorB;
    }

    /**
     * Set aliasesInteractorA
     *
     * @param text $aliasesInteractorA
     */
    public function setAliasesInteractorA($aliasesInteractorA)
    {
        $this->aliasesInteractorA = $aliasesInteractorA;
    }

    /**
     * Get aliasesInteractorA
     *
     * @return text 
     */
    public function getAliasesInteractorA()
    {
        return $this->aliasesInteractorA;
    }

    /**
     * Set aliasesInteractorB
     *
     * @param text $aliasesInteractorB
     */
    public function setAliasesInteractorB($aliasesInteractorB)
    {
        $this->aliasesInteractorB = $aliasesInteractorB;
    }

    /**
     * Get aliasesInteractorB
     *
     * @return text 
     */
    public function getAliasesInteractorB()
    {
        return $this->aliasesInteractorB;
    }

    /**
     * Set interactionDetectionMethod
     *
     * @param string $interactionDetectionMethod
     */
    public function setInteractionDetectionMethod($interactionDetectionMethod)
    {
        $this->interactionDetectionMethod = $interactionDetectionMethod;
    }

    /**
     * Get interactionDetectionMethod
     *
     * @return string 
     */
    public function getInteractionDetectionMethod()
    {
        return $this->interactionDetectionMethod;
    }

    /**
     * Set publication1stAuthor
     *
     * @param string $publication1stAuthor
     */
    public function setPublication1stAuthor($publication1stAuthor)
    {
        $this->publication1stAuthor = $publication1stAuthor;
    }

    /**
     * Get publication1stAuthor
     *
     * @return string 
     */
    public function getPublication1stAuthor()
    {
        return $this->publication1stAuthor;
    }

    /**
     * Set publicationIdentifiers
     *
     * @param string $publicationIdentifiers
     */
    public function setPublicationIdentifiers($publicationIdentifiers)
    {
        $this->publicationIdentifiers = $publicationIdentifiers;
    }

    /**
     * Get publicationIdentifiers
     *
     * @return string 
     */
    public function getPublicationIdentifiers()
    {
        return $this->publicationIdentifiers;
    }

    /**
     * Set taxidInteractorA
     *
     * @param string $taxidInteractorA
     */
    public function setTaxidInteractorA($taxidInteractorA)
    {
        $this->taxidInteractorA = $taxidInteractorA;
    }

    /**
     * Get taxidInteractorA
     *
     * @return string 
     */
    public function getTaxidInteractorA()
    {
        return $this->taxidInteractorA;
    }

    /**
     * Set taxidInteractorB
     *
     * @param string $taxidInteractorB
     */
    public function setTaxidInteractorB($taxidInteractorB)
    {
        $this->taxidInteractorB = $taxidInteractorB;
    }

    /**
     * Get taxidInteractorB
     *
     * @return string 
     */
    public function getTaxidInteractorB()
    {
        return $this->taxidInteractorB;
    }

    /**
     * Set interactionTypes
     *
     * @param string $interactionTypes
     */
    public function setInteractionTypes($interactionTypes)
    {
        $this->interactionTypes = $interactionTypes;
    }

    /**
     * Get interactionTypes
     *
     * @return string 
     */
    public function getInteractionTypes()
    {
        return $this->interactionTypes;
    }

    /**
     * Set sourceDatabase
     *
     * @param string $sourceDatabase
     */
    public function setSourceDatabase($sourceDatabase)
    {
        $this->sourceDatabase = $sourceDatabase;
    }

    /**
     * Get sourceDatabase
     *
     * @return string 
     */
    public function getSourceDatabase()
    {
        return $this->sourceDatabase;
    }

    /**
     * Set interactionIdentifiers
     *
     * @param string $interactionIdentifiers
     */
    public function setInteractionIdentifiers($interactionIdentifiers)
    {
        $this->interactionIdentifiers = $interactionIdentifiers;
    }

    /**
     * Get interactionIdentifiers
     *
     * @return string 
     */
    public function getInteractionIdentifiers()
    {
        return $this->interactionIdentifiers;
    }

    /**
     * Set confidenceValues
     *
     * @param string $confidenceValues
     */
    public function setConfidenceValues($confidenceValues)
    {
        $this->confidenceValues = $confidenceValues;
    }

    /**
     * Get confidenceValues
     *
     * @return string 
     */
    public function getConfidenceValues()
    {
        return $this->confidenceValues;
    }
}