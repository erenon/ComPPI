<?php

namespace Comppi\LoaderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class PagosubHsTest
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    
    /**
     * @ORM\Column(type="string", length="1")
     */
    protected $proteinNumber;
    
    /**
     * @ORM\Column(type="string", length="110")
     */
    protected $annotation;
    
    /**
     * @ORM\Column(type="string", length="11")
     */
    protected $probabilityGolgi;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotGolgi;
    
    /**
     * @ORM\Column(type="string", length="11")
     */
    protected $probabilityNucleus;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotNucleus;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityExtracellular;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotExtracellular;
    
    /**
     * @ORM\Column(type="string", length="11")
     */
    protected $probabilityMitochondrion;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotMitochondrion;
    
    /**
     * @ORM\Column(type="string", length="9")
     */
    protected $probabilityCytoplasm;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityNotCytoplasm;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityPlasmaMembrane;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotPlasmaMembrane;
    
    /**
     * @ORM\Column(type="string", length="11")
     */
    protected $probabilityLysosome;
    
    /**
     * @ORM\Column(type="string", length="3")
     */
    protected $probabilityNotLysosome;
    
    /**
     * @ORM\Column(type="string", length="11")
     */
    protected $probabilityPeroxisome;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotPeroxisome;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityEndoplasmicReticulum;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotEndoplasmicReticulum;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityIonBinding;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotIonBinding;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityProteinBinding;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityNotProteinBinding;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilitySignalTransducerActivity;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotSignalTransducerActivity;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityHydrolaseActivity;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotHydrolaseActivity;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityLyaseActivity;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotLyaseActivity;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityBinding;
    
    /**
     * @ORM\Column(type="string", length="9")
     */
    protected $probabilityNotBinding;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityStructuralMoleculeActivity;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotStructuralMoleculeActivity;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityTransporterActivity;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotTransporterActivity;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityCatalyticActivity;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotCatalyticActivity;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityTransferaseActivity;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotTransferaseActivity;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityNucleicAcidBinding;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotNucleicAcidBinding;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityOxidoreductaseActivity;
    
    /**
     * @ORM\Column(type="string", length="8")
     */
    protected $probabilityNotOxidoreductaseActivity;
    
    /**
     * @ORM\Column(type="string", length="10")
     */
    protected $probabilityNucleotideBinding;
    
    /**
     * @ORM\Column(type="string", length="9")
     */
    protected $probabilityNotNucleotideBinding;
    

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
     * Set proteinNumber
     *
     * @param string $proteinNumber
     */
    public function setProteinNumber($proteinNumber)
    {
        $this->proteinNumber = $proteinNumber;
    }

    /**
     * Get proteinNumber
     *
     * @return string 
     */
    public function getProteinNumber()
    {
        return $this->proteinNumber;
    }

    /**
     * Set annotation
     *
     * @param string $annotation
     */
    public function setAnnotation($annotation)
    {
        $this->annotation = $annotation;
    }

    /**
     * Get annotation
     *
     * @return string 
     */
    public function getAnnotation()
    {
        return $this->annotation;
    }

    /**
     * Set probabilityGolgi
     *
     * @param string $probabilityGolgi
     */
    public function setProbabilityGolgi($probabilityGolgi)
    {
        $this->probabilityGolgi = $probabilityGolgi;
    }

    /**
     * Get probabilityGolgi
     *
     * @return string 
     */
    public function getProbabilityGolgi()
    {
        return $this->probabilityGolgi;
    }

    /**
     * Set probabilityNotGolgi
     *
     * @param string $probabilityNotGolgi
     */
    public function setProbabilityNotGolgi($probabilityNotGolgi)
    {
        $this->probabilityNotGolgi = $probabilityNotGolgi;
    }

    /**
     * Get probabilityNotGolgi
     *
     * @return string 
     */
    public function getProbabilityNotGolgi()
    {
        return $this->probabilityNotGolgi;
    }

    /**
     * Set probabilityNucleus
     *
     * @param string $probabilityNucleus
     */
    public function setProbabilityNucleus($probabilityNucleus)
    {
        $this->probabilityNucleus = $probabilityNucleus;
    }

    /**
     * Get probabilityNucleus
     *
     * @return string 
     */
    public function getProbabilityNucleus()
    {
        return $this->probabilityNucleus;
    }

    /**
     * Set probabilityNotNucleus
     *
     * @param string $probabilityNotNucleus
     */
    public function setProbabilityNotNucleus($probabilityNotNucleus)
    {
        $this->probabilityNotNucleus = $probabilityNotNucleus;
    }

    /**
     * Get probabilityNotNucleus
     *
     * @return string 
     */
    public function getProbabilityNotNucleus()
    {
        return $this->probabilityNotNucleus;
    }

    /**
     * Set probabilityExtracellular
     *
     * @param string $probabilityExtracellular
     */
    public function setProbabilityExtracellular($probabilityExtracellular)
    {
        $this->probabilityExtracellular = $probabilityExtracellular;
    }

    /**
     * Get probabilityExtracellular
     *
     * @return string 
     */
    public function getProbabilityExtracellular()
    {
        return $this->probabilityExtracellular;
    }

    /**
     * Set probabilityNotExtracellular
     *
     * @param string $probabilityNotExtracellular
     */
    public function setProbabilityNotExtracellular($probabilityNotExtracellular)
    {
        $this->probabilityNotExtracellular = $probabilityNotExtracellular;
    }

    /**
     * Get probabilityNotExtracellular
     *
     * @return string 
     */
    public function getProbabilityNotExtracellular()
    {
        return $this->probabilityNotExtracellular;
    }

    /**
     * Set probabilityMitochondrion
     *
     * @param string $probabilityMitochondrion
     */
    public function setProbabilityMitochondrion($probabilityMitochondrion)
    {
        $this->probabilityMitochondrion = $probabilityMitochondrion;
    }

    /**
     * Get probabilityMitochondrion
     *
     * @return string 
     */
    public function getProbabilityMitochondrion()
    {
        return $this->probabilityMitochondrion;
    }

    /**
     * Set probabilityNotMitochondrion
     *
     * @param string $probabilityNotMitochondrion
     */
    public function setProbabilityNotMitochondrion($probabilityNotMitochondrion)
    {
        $this->probabilityNotMitochondrion = $probabilityNotMitochondrion;
    }

    /**
     * Get probabilityNotMitochondrion
     *
     * @return string 
     */
    public function getProbabilityNotMitochondrion()
    {
        return $this->probabilityNotMitochondrion;
    }

    /**
     * Set probabilityCytoplasm
     *
     * @param string $probabilityCytoplasm
     */
    public function setProbabilityCytoplasm($probabilityCytoplasm)
    {
        $this->probabilityCytoplasm = $probabilityCytoplasm;
    }

    /**
     * Get probabilityCytoplasm
     *
     * @return string 
     */
    public function getProbabilityCytoplasm()
    {
        return $this->probabilityCytoplasm;
    }

    /**
     * Set probabilityNotCytoplasm
     *
     * @param string $probabilityNotCytoplasm
     */
    public function setProbabilityNotCytoplasm($probabilityNotCytoplasm)
    {
        $this->probabilityNotCytoplasm = $probabilityNotCytoplasm;
    }

    /**
     * Get probabilityNotCytoplasm
     *
     * @return string 
     */
    public function getProbabilityNotCytoplasm()
    {
        return $this->probabilityNotCytoplasm;
    }

    /**
     * Set probabilityPlasmaMembrane
     *
     * @param string $probabilityPlasmaMembrane
     */
    public function setProbabilityPlasmaMembrane($probabilityPlasmaMembrane)
    {
        $this->probabilityPlasmaMembrane = $probabilityPlasmaMembrane;
    }

    /**
     * Get probabilityPlasmaMembrane
     *
     * @return string 
     */
    public function getProbabilityPlasmaMembrane()
    {
        return $this->probabilityPlasmaMembrane;
    }

    /**
     * Set probabilityNotPlasmaMembrane
     *
     * @param string $probabilityNotPlasmaMembrane
     */
    public function setProbabilityNotPlasmaMembrane($probabilityNotPlasmaMembrane)
    {
        $this->probabilityNotPlasmaMembrane = $probabilityNotPlasmaMembrane;
    }

    /**
     * Get probabilityNotPlasmaMembrane
     *
     * @return string 
     */
    public function getProbabilityNotPlasmaMembrane()
    {
        return $this->probabilityNotPlasmaMembrane;
    }

    /**
     * Set probabilityLysosome
     *
     * @param string $probabilityLysosome
     */
    public function setProbabilityLysosome($probabilityLysosome)
    {
        $this->probabilityLysosome = $probabilityLysosome;
    }

    /**
     * Get probabilityLysosome
     *
     * @return string 
     */
    public function getProbabilityLysosome()
    {
        return $this->probabilityLysosome;
    }

    /**
     * Set probabilityNotLysosome
     *
     * @param string $probabilityNotLysosome
     */
    public function setProbabilityNotLysosome($probabilityNotLysosome)
    {
        $this->probabilityNotLysosome = $probabilityNotLysosome;
    }

    /**
     * Get probabilityNotLysosome
     *
     * @return string 
     */
    public function getProbabilityNotLysosome()
    {
        return $this->probabilityNotLysosome;
    }

    /**
     * Set probabilityPeroxisome
     *
     * @param string $probabilityPeroxisome
     */
    public function setProbabilityPeroxisome($probabilityPeroxisome)
    {
        $this->probabilityPeroxisome = $probabilityPeroxisome;
    }

    /**
     * Get probabilityPeroxisome
     *
     * @return string 
     */
    public function getProbabilityPeroxisome()
    {
        return $this->probabilityPeroxisome;
    }

    /**
     * Set probabilityNotPeroxisome
     *
     * @param string $probabilityNotPeroxisome
     */
    public function setProbabilityNotPeroxisome($probabilityNotPeroxisome)
    {
        $this->probabilityNotPeroxisome = $probabilityNotPeroxisome;
    }

    /**
     * Get probabilityNotPeroxisome
     *
     * @return string 
     */
    public function getProbabilityNotPeroxisome()
    {
        return $this->probabilityNotPeroxisome;
    }

    /**
     * Set probabilityEndoplasmicReticulum
     *
     * @param string $probabilityEndoplasmicReticulum
     */
    public function setProbabilityEndoplasmicReticulum($probabilityEndoplasmicReticulum)
    {
        $this->probabilityEndoplasmicReticulum = $probabilityEndoplasmicReticulum;
    }

    /**
     * Get probabilityEndoplasmicReticulum
     *
     * @return string 
     */
    public function getProbabilityEndoplasmicReticulum()
    {
        return $this->probabilityEndoplasmicReticulum;
    }

    /**
     * Set probabilityNotEndoplasmicReticulum
     *
     * @param string $probabilityNotEndoplasmicReticulum
     */
    public function setProbabilityNotEndoplasmicReticulum($probabilityNotEndoplasmicReticulum)
    {
        $this->probabilityNotEndoplasmicReticulum = $probabilityNotEndoplasmicReticulum;
    }

    /**
     * Get probabilityNotEndoplasmicReticulum
     *
     * @return string 
     */
    public function getProbabilityNotEndoplasmicReticulum()
    {
        return $this->probabilityNotEndoplasmicReticulum;
    }

    /**
     * Set probabilityIonBinding
     *
     * @param string $probabilityIonBinding
     */
    public function setProbabilityIonBinding($probabilityIonBinding)
    {
        $this->probabilityIonBinding = $probabilityIonBinding;
    }

    /**
     * Get probabilityIonBinding
     *
     * @return string 
     */
    public function getProbabilityIonBinding()
    {
        return $this->probabilityIonBinding;
    }

    /**
     * Set probabilityNotIonBinding
     *
     * @param string $probabilityNotIonBinding
     */
    public function setProbabilityNotIonBinding($probabilityNotIonBinding)
    {
        $this->probabilityNotIonBinding = $probabilityNotIonBinding;
    }

    /**
     * Get probabilityNotIonBinding
     *
     * @return string 
     */
    public function getProbabilityNotIonBinding()
    {
        return $this->probabilityNotIonBinding;
    }

    /**
     * Set probabilityProteinBinding
     *
     * @param string $probabilityProteinBinding
     */
    public function setProbabilityProteinBinding($probabilityProteinBinding)
    {
        $this->probabilityProteinBinding = $probabilityProteinBinding;
    }

    /**
     * Get probabilityProteinBinding
     *
     * @return string 
     */
    public function getProbabilityProteinBinding()
    {
        return $this->probabilityProteinBinding;
    }

    /**
     * Set probabilityNotProteinBinding
     *
     * @param string $probabilityNotProteinBinding
     */
    public function setProbabilityNotProteinBinding($probabilityNotProteinBinding)
    {
        $this->probabilityNotProteinBinding = $probabilityNotProteinBinding;
    }

    /**
     * Get probabilityNotProteinBinding
     *
     * @return string 
     */
    public function getProbabilityNotProteinBinding()
    {
        return $this->probabilityNotProteinBinding;
    }

    /**
     * Set probabilitySignalTransducerActivity
     *
     * @param string $probabilitySignalTransducerActivity
     */
    public function setProbabilitySignalTransducerActivity($probabilitySignalTransducerActivity)
    {
        $this->probabilitySignalTransducerActivity = $probabilitySignalTransducerActivity;
    }

    /**
     * Get probabilitySignalTransducerActivity
     *
     * @return string 
     */
    public function getProbabilitySignalTransducerActivity()
    {
        return $this->probabilitySignalTransducerActivity;
    }

    /**
     * Set probabilityNotSignalTransducerActivity
     *
     * @param string $probabilityNotSignalTransducerActivity
     */
    public function setProbabilityNotSignalTransducerActivity($probabilityNotSignalTransducerActivity)
    {
        $this->probabilityNotSignalTransducerActivity = $probabilityNotSignalTransducerActivity;
    }

    /**
     * Get probabilityNotSignalTransducerActivity
     *
     * @return string 
     */
    public function getProbabilityNotSignalTransducerActivity()
    {
        return $this->probabilityNotSignalTransducerActivity;
    }

    /**
     * Set probabilityHydrolaseActivity
     *
     * @param string $probabilityHydrolaseActivity
     */
    public function setProbabilityHydrolaseActivity($probabilityHydrolaseActivity)
    {
        $this->probabilityHydrolaseActivity = $probabilityHydrolaseActivity;
    }

    /**
     * Get probabilityHydrolaseActivity
     *
     * @return string 
     */
    public function getProbabilityHydrolaseActivity()
    {
        return $this->probabilityHydrolaseActivity;
    }

    /**
     * Set probabilityNotHydrolaseActivity
     *
     * @param string $probabilityNotHydrolaseActivity
     */
    public function setProbabilityNotHydrolaseActivity($probabilityNotHydrolaseActivity)
    {
        $this->probabilityNotHydrolaseActivity = $probabilityNotHydrolaseActivity;
    }

    /**
     * Get probabilityNotHydrolaseActivity
     *
     * @return string 
     */
    public function getProbabilityNotHydrolaseActivity()
    {
        return $this->probabilityNotHydrolaseActivity;
    }

    /**
     * Set probabilityLyaseActivity
     *
     * @param string $probabilityLyaseActivity
     */
    public function setProbabilityLyaseActivity($probabilityLyaseActivity)
    {
        $this->probabilityLyaseActivity = $probabilityLyaseActivity;
    }

    /**
     * Get probabilityLyaseActivity
     *
     * @return string 
     */
    public function getProbabilityLyaseActivity()
    {
        return $this->probabilityLyaseActivity;
    }

    /**
     * Set probabilityNotLyaseActivity
     *
     * @param string $probabilityNotLyaseActivity
     */
    public function setProbabilityNotLyaseActivity($probabilityNotLyaseActivity)
    {
        $this->probabilityNotLyaseActivity = $probabilityNotLyaseActivity;
    }

    /**
     * Get probabilityNotLyaseActivity
     *
     * @return string 
     */
    public function getProbabilityNotLyaseActivity()
    {
        return $this->probabilityNotLyaseActivity;
    }

    /**
     * Set probabilityBinding
     *
     * @param string $probabilityBinding
     */
    public function setProbabilityBinding($probabilityBinding)
    {
        $this->probabilityBinding = $probabilityBinding;
    }

    /**
     * Get probabilityBinding
     *
     * @return string 
     */
    public function getProbabilityBinding()
    {
        return $this->probabilityBinding;
    }

    /**
     * Set probabilityNotBinding
     *
     * @param string $probabilityNotBinding
     */
    public function setProbabilityNotBinding($probabilityNotBinding)
    {
        $this->probabilityNotBinding = $probabilityNotBinding;
    }

    /**
     * Get probabilityNotBinding
     *
     * @return string 
     */
    public function getProbabilityNotBinding()
    {
        return $this->probabilityNotBinding;
    }

    /**
     * Set probabilityStructuralMoleculeActivity
     *
     * @param string $probabilityStructuralMoleculeActivity
     */
    public function setProbabilityStructuralMoleculeActivity($probabilityStructuralMoleculeActivity)
    {
        $this->probabilityStructuralMoleculeActivity = $probabilityStructuralMoleculeActivity;
    }

    /**
     * Get probabilityStructuralMoleculeActivity
     *
     * @return string 
     */
    public function getProbabilityStructuralMoleculeActivity()
    {
        return $this->probabilityStructuralMoleculeActivity;
    }

    /**
     * Set probabilityNotStructuralMoleculeActivity
     *
     * @param string $probabilityNotStructuralMoleculeActivity
     */
    public function setProbabilityNotStructuralMoleculeActivity($probabilityNotStructuralMoleculeActivity)
    {
        $this->probabilityNotStructuralMoleculeActivity = $probabilityNotStructuralMoleculeActivity;
    }

    /**
     * Get probabilityNotStructuralMoleculeActivity
     *
     * @return string 
     */
    public function getProbabilityNotStructuralMoleculeActivity()
    {
        return $this->probabilityNotStructuralMoleculeActivity;
    }

    /**
     * Set probabilityTransporterActivity
     *
     * @param string $probabilityTransporterActivity
     */
    public function setProbabilityTransporterActivity($probabilityTransporterActivity)
    {
        $this->probabilityTransporterActivity = $probabilityTransporterActivity;
    }

    /**
     * Get probabilityTransporterActivity
     *
     * @return string 
     */
    public function getProbabilityTransporterActivity()
    {
        return $this->probabilityTransporterActivity;
    }

    /**
     * Set probabilityNotTransporterActivity
     *
     * @param string $probabilityNotTransporterActivity
     */
    public function setProbabilityNotTransporterActivity($probabilityNotTransporterActivity)
    {
        $this->probabilityNotTransporterActivity = $probabilityNotTransporterActivity;
    }

    /**
     * Get probabilityNotTransporterActivity
     *
     * @return string 
     */
    public function getProbabilityNotTransporterActivity()
    {
        return $this->probabilityNotTransporterActivity;
    }

    /**
     * Set probabilityCatalyticActivity
     *
     * @param string $probabilityCatalyticActivity
     */
    public function setProbabilityCatalyticActivity($probabilityCatalyticActivity)
    {
        $this->probabilityCatalyticActivity = $probabilityCatalyticActivity;
    }

    /**
     * Get probabilityCatalyticActivity
     *
     * @return string 
     */
    public function getProbabilityCatalyticActivity()
    {
        return $this->probabilityCatalyticActivity;
    }

    /**
     * Set probabilityNotCatalyticActivity
     *
     * @param string $probabilityNotCatalyticActivity
     */
    public function setProbabilityNotCatalyticActivity($probabilityNotCatalyticActivity)
    {
        $this->probabilityNotCatalyticActivity = $probabilityNotCatalyticActivity;
    }

    /**
     * Get probabilityNotCatalyticActivity
     *
     * @return string 
     */
    public function getProbabilityNotCatalyticActivity()
    {
        return $this->probabilityNotCatalyticActivity;
    }

    /**
     * Set probabilityTransferaseActivity
     *
     * @param string $probabilityTransferaseActivity
     */
    public function setProbabilityTransferaseActivity($probabilityTransferaseActivity)
    {
        $this->probabilityTransferaseActivity = $probabilityTransferaseActivity;
    }

    /**
     * Get probabilityTransferaseActivity
     *
     * @return string 
     */
    public function getProbabilityTransferaseActivity()
    {
        return $this->probabilityTransferaseActivity;
    }

    /**
     * Set probabilityNotTransferaseActivity
     *
     * @param string $probabilityNotTransferaseActivity
     */
    public function setProbabilityNotTransferaseActivity($probabilityNotTransferaseActivity)
    {
        $this->probabilityNotTransferaseActivity = $probabilityNotTransferaseActivity;
    }

    /**
     * Get probabilityNotTransferaseActivity
     *
     * @return string 
     */
    public function getProbabilityNotTransferaseActivity()
    {
        return $this->probabilityNotTransferaseActivity;
    }

    /**
     * Set probabilityNucleicAcidBinding
     *
     * @param string $probabilityNucleicAcidBinding
     */
    public function setProbabilityNucleicAcidBinding($probabilityNucleicAcidBinding)
    {
        $this->probabilityNucleicAcidBinding = $probabilityNucleicAcidBinding;
    }

    /**
     * Get probabilityNucleicAcidBinding
     *
     * @return string 
     */
    public function getProbabilityNucleicAcidBinding()
    {
        return $this->probabilityNucleicAcidBinding;
    }

    /**
     * Set probabilityNotNucleicAcidBinding
     *
     * @param string $probabilityNotNucleicAcidBinding
     */
    public function setProbabilityNotNucleicAcidBinding($probabilityNotNucleicAcidBinding)
    {
        $this->probabilityNotNucleicAcidBinding = $probabilityNotNucleicAcidBinding;
    }

    /**
     * Get probabilityNotNucleicAcidBinding
     *
     * @return string 
     */
    public function getProbabilityNotNucleicAcidBinding()
    {
        return $this->probabilityNotNucleicAcidBinding;
    }

    /**
     * Set probabilityOxidoreductaseActivity
     *
     * @param string $probabilityOxidoreductaseActivity
     */
    public function setProbabilityOxidoreductaseActivity($probabilityOxidoreductaseActivity)
    {
        $this->probabilityOxidoreductaseActivity = $probabilityOxidoreductaseActivity;
    }

    /**
     * Get probabilityOxidoreductaseActivity
     *
     * @return string 
     */
    public function getProbabilityOxidoreductaseActivity()
    {
        return $this->probabilityOxidoreductaseActivity;
    }

    /**
     * Set probabilityNotOxidoreductaseActivity
     *
     * @param string $probabilityNotOxidoreductaseActivity
     */
    public function setProbabilityNotOxidoreductaseActivity($probabilityNotOxidoreductaseActivity)
    {
        $this->probabilityNotOxidoreductaseActivity = $probabilityNotOxidoreductaseActivity;
    }

    /**
     * Get probabilityNotOxidoreductaseActivity
     *
     * @return string 
     */
    public function getProbabilityNotOxidoreductaseActivity()
    {
        return $this->probabilityNotOxidoreductaseActivity;
    }

    /**
     * Set probabilityNucleotideBinding
     *
     * @param string $probabilityNucleotideBinding
     */
    public function setProbabilityNucleotideBinding($probabilityNucleotideBinding)
    {
        $this->probabilityNucleotideBinding = $probabilityNucleotideBinding;
    }

    /**
     * Get probabilityNucleotideBinding
     *
     * @return string 
     */
    public function getProbabilityNucleotideBinding()
    {
        return $this->probabilityNucleotideBinding;
    }

    /**
     * Set probabilityNotNucleotideBinding
     *
     * @param string $probabilityNotNucleotideBinding
     */
    public function setProbabilityNotNucleotideBinding($probabilityNotNucleotideBinding)
    {
        $this->probabilityNotNucleotideBinding = $probabilityNotNucleotideBinding;
    }

    /**
     * Get probabilityNotNucleotideBinding
     *
     * @return string 
     */
    public function getProbabilityNotNucleotideBinding()
    {
        return $this->probabilityNotNucleotideBinding;
    }
}