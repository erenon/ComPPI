<?php

namespace Comppi\LoaderBundle\Entity;

/**
 * @orm:Entity
 */
class BiogridTest
{
    /**
     * @orm:Id
     * @orm:Column(type="integer")
     * @orm:GeneratedValue(strategy="AUTO")
     */
    protected $id;

    
    /**
     * @orm:Column(type="string", length="39")
     */
    protected $iDInteractorA;
    
    /**
     * @orm:Column(type="string", length="39")
     */
    protected $iDInteractorB;
    
    /**
     * @orm:Column(type="string", length="63")
     */
    protected $altIDsInteractorA;
    
    /**
     * @orm:Column(type="string", length="28")
     */
    protected $altIDsInteractorB;
    
    /**
     * @orm:Column(type="text")
     */
    protected $aliasesInteractorA;
    
    /**
     * @orm:Column(type="text")
     */
    protected $aliasesInteractorB;
    
    /**
     * @orm:Column(type="string", length="28")
     */
    protected $interactionDetectionMethod;
    
    /**
     * @orm:Column(type="string", length="24")
     */
    protected $publication1stAuthor;
    
    /**
     * @orm:Column(type="string", length="15")
     */
    protected $publicationIdentifiers;
    
    /**
     * @orm:Column(type="string", length="10")
     */
    protected $taxidInteractorA;
    
    /**
     * @orm:Column(type="string", length="10")
     */
    protected $taxidInteractorB;
    
    /**
     * @orm:Column(type="string", length="36")
     */
    protected $interactionTypes;
    
    /**
     * @orm:Column(type="string", length="22")
     */
    protected $sourceDatabase;
    
    /**
     * @orm:Column(type="string", length="8")
     */
    protected $interactionIdentifiers;
    
    /**
     * @orm:Column(type="string", length="2")
     */
    protected $confidenceValues;
    

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
     * @return string $iDInteractorA
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
     * @return string $iDInteractorB
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
     * @return string $altIDsInteractorA
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
     * @return string $altIDsInteractorB
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
     * @return text $aliasesInteractorA
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
     * @return text $aliasesInteractorB
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
     * @return string $interactionDetectionMethod
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
     * @return string $publication1stAuthor
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
     * @return string $publicationIdentifiers
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
     * @return string $taxidInteractorA
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
     * @return string $taxidInteractorB
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
     * @return string $interactionTypes
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
     * @return string $sourceDatabase
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
     * @return string $interactionIdentifiers
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
     * @return string $confidenceValues
     */
    public function getConfidenceValues()
    {
        return $this->confidenceValues;
    }
}