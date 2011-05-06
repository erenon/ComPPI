<?php

namespace Comppi\LoaderBundle\Entity;

/**
 * @orm:Entity
 */
class Esldb_hs
{
    /**
     * @orm:Id
     * @orm:Column(type="integer")
     * @orm:GeneratedValue(strategy="AUTO")
     */
    protected $id;

    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $eSLDB_code;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Original_Database_Code;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Experimental_annotation;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $SwissProt_fulltext_annotation;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $SwissProt_entry;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Similarity_based_annotation;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $SwissProt_homologue;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $E_value;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Prediction;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Aminoacidic_sequence;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Common_mame
;
    