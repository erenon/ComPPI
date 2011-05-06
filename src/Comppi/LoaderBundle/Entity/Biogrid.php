<?php

namespace Comppi\LoaderBundle\Entity;

/**
 * @orm:Entity
 */
class Biogrid
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
    protected $ID_Interactor_A;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $ID_Interactor_B;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Alt_IDs_Interactor_A;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Alt_IDs_Interactor_B;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Aliases_Interactor_A;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Aliases_Interactor_B;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Interaction_Detection_Method;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Publication_1st_Author;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Publication_Identifiers;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Taxid_Interactor_A;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Taxid_Interactor_B;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Interaction_Types;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Source_Database;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Interaction_Identifiers;
    
    /**
     * @orm:Column(type="string", length="512")
     */
    protected $Confidence_Values
;
    