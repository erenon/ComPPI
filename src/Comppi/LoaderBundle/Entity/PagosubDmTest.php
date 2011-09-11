<?php

namespace Comppi\LoaderBundle\Entity;

/**
 * @orm:Entity
 */
class PagosubDmTest
{
    /**
     * @orm:Id
     * @orm:Column(type="integer")
     * @orm:GeneratedValue(strategy="AUTO")
     */
    protected $id;

    
    /**
     * @orm:Column(type="string", length="1")
     */
    protected $proteinNumber;
    
    /**
     * @orm:Column(type="string", length="143")
     */
    protected $annotation;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityGolgi;
    
    /**
     * @orm:Column(type="string", length="8")
     */
    protected $probabilityNotGolgi;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityNucleus;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityNotNucleus;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityExtracellular;
    
    /**
     * @orm:Column(type="string", length="8")
     */
    protected $probabilityNotExtracellular;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityMitochondrion;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityNotMitochondrion;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityCytoplasm;
    
    /**
     * @orm:Column(type="string", length="10")
     */
    protected $probabilityNotCytoplasm;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityPlasmaMembrane;
    
    /**
     * @orm:Column(type="string", length="8")
     */
    protected $probabilityNotPlasmaMembrane;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityLysosome;
    
    /**
     * @orm:Column(type="string", length="3")
     */
    protected $probabilityNotLysosome;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityPeroxisome;
    
    /**
     * @orm:Column(type="string", length="3")
     */
    protected $probabilityNotPeroxisome;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityEndoplasmicReticulum;
    
    /**
     * @orm:Column(type="string", length="8")
     */
    protected $probabilityNotEndoplasmicReticulum;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityIonBinding;
    
    /**
     * @orm:Column(type="string", length="8")
     */
    protected $probabilityNotIonBinding;
    
    /**
     * @orm:Column(type="string", length="10")
     */
    protected $probabilityProteinBinding;
    
    /**
     * @orm:Column(type="string", length="10")
     */
    protected $probabilityNotProteinBinding;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilitySignalTransducerActivity;
    
    /**
     * @orm:Column(type="string", length="8")
     */
    protected $probabilityNotSignalTransducerActivity;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityHydrolaseActivity;
    
    /**
     * @orm:Column(type="string", length="10")
     */
    protected $probabilityNotHydrolaseActivity;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityLyaseActivity;
    
    /**
     * @orm:Column(type="string", length="8")
     */
    protected $probabilityNotLyaseActivity;
    
    /**
     * @orm:Column(type="string", length="10")
     */
    protected $probabilityBinding;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityNotBinding;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityStructuralMoleculeActivity;
    
    /**
     * @orm:Column(type="string", length="8")
     */
    protected $probabilityNotStructuralMoleculeActivity;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityTransporterActivity;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityNotTransporterActivity;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityCatalyticActivity;
    
    /**
     * @orm:Column(type="string", length="10")
     */
    protected $probabilityNotCatalyticActivity;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityTransferaseActivity;
    
    /**
     * @orm:Column(type="string", length="8")
     */
    protected $probabilityNotTransferaseActivity;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityNucleicAcidBinding;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityNotNucleicAcidBinding;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityOxidoreductaseActivity;
    
    /**
     * @orm:Column(type="string", length="8")
     */
    protected $probabilityNotOxidoreductaseActivity;
    
    /**
     * @orm:Column(type="string", length="11")
     */
    protected $probabilityNucleotideBinding;
    
    /**
     * @orm:Column(type="string", length="12")
     */
    protected $probabilityNotNucleotideBinding;
    

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
     * @return string $proteinNumber
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
     * @return string $annotation
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
     * @return string $probabilityGolgi
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
     * @return string $probabilityNotGolgi
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
     * @return string $probabilityNucleus
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
     * @return string $probabilityNotNucleus
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
     * @return string $probabilityExtracellular
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
     * @return string $probabilityNotExtracellular
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
     * @return string $probabilityMitochondrion
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
     * @return string $probabilityNotMitochondrion
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
     * @return string $probabilityCytoplasm
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
     * @return string $probabilityNotCytoplasm
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
     * @return string $probabilityPlasmaMembrane
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
     * @return string $probabilityNotPlasmaMembrane
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
     * @return string $probabilityLysosome
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
     * @return string $probabilityNotLysosome
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
     * @return string $probabilityPeroxisome
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
     * @return string $probabilityNotPeroxisome
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
     * @return string $probabilityEndoplasmicReticulum
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
     * @return string $probabilityNotEndoplasmicReticulum
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
     * @return string $probabilityIonBinding
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
     * @return string $probabilityNotIonBinding
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
     * @return string $probabilityProteinBinding
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
     * @return string $probabilityNotProteinBinding
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
     * @return string $probabilitySignalTransducerActivity
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
     * @return string $probabilityNotSignalTransducerActivity
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
     * @return string $probabilityHydrolaseActivity
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
     * @return string $probabilityNotHydrolaseActivity
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
     * @return string $probabilityLyaseActivity
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
     * @return string $probabilityNotLyaseActivity
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
     * @return string $probabilityBinding
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
     * @return string $probabilityNotBinding
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
     * @return string $probabilityStructuralMoleculeActivity
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
     * @return string $probabilityNotStructuralMoleculeActivity
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
     * @return string $probabilityTransporterActivity
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
     * @return string $probabilityNotTransporterActivity
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
     * @return string $probabilityCatalyticActivity
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
     * @return string $probabilityNotCatalyticActivity
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
     * @return string $probabilityTransferaseActivity
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
     * @return string $probabilityNotTransferaseActivity
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
     * @return string $probabilityNucleicAcidBinding
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
     * @return string $probabilityNotNucleicAcidBinding
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
     * @return string $probabilityOxidoreductaseActivity
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
     * @return string $probabilityNotOxidoreductaseActivity
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
     * @return string $probabilityNucleotideBinding
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
     * @return string $probabilityNotNucleotideBinding
     */
    public function getProbabilityNotNucleotideBinding()
    {
        return $this->probabilityNotNucleotideBinding;
    }
}