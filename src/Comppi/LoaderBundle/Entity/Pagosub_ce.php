<?php

namespace Comppi\LoaderBundle\Entity;

/**
 * @orm:Entity
 */
class Pagosub_ce
{
    /**
     * @orm:Id
     * @orm:Column(type="integer")
     * @orm:GeneratedValue(strategy="AUTO")
     */
    protected $id;

    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $protein_number;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $annotation;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_golgi;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_golgi;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_nucleus;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_nucleus;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_extracellular;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_extracellular;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_mitochondrion;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_mitochondrion;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_cytoplasm;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_cytoplasm;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_plasma_membrane;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_plasma_membrane;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_lysosome;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_lysosome;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_peroxisome;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_peroxisome;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_endoplasmic_reticulum;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_endoplasmic_reticulum;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_ion_binding;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_ion_binding;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_protein_binding;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_protein_binding;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_signal_transducer_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_signal_transducer_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_hydrolase_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_hydrolase_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_lyase_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_lyase_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_binding;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_binding;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_structural_molecule_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_structural_molecule_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_transporter_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_transporter_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_catalytic_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_catalytic_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_transferase_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_transferase_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_nucleic_acid_binding;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_nucleic_acid_binding;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_oxidoreductase_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_oxidoreductase_activity;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_nucleotide_binding;
    
    /**
     * @orm:Column(type="string", length="255")
     */
    protected $probability_Not_nucleotide_binding;
    
}