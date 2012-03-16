<?php

namespace Comppi\BuildBundle\Service\LocalizationTranslator;

class LocalizationTranslator
{
    private $localizations = array (
        'Nucleus',
        'cytoplasm',
        'Peroxysomes',
        'Mitochondria',
        'Plasma_Membrane',
        'Endoplasmic_Reticulum',
        'Extracellular/Secretory',
        'Golgi',
        'Lysosomes',
        // Test localizations
        'Compact' => array(
            'SubFoo',
            'SubCompact' => array(
                'SubSubBar',
                'SubSubBaz'
            ),
            'SubBar'
        )
    );
    
    private $localizationTree = array();
    private $localizationToId;
    
    public function __construct() {
        $this->buildTree('Protein', $this->localizations, 0);
        $this->initLocalizationToIdMap();
    }
    
    private function buildTree($localization, $node, $id) {
        if (is_array($node)) {
            $nodeId = $id;
            $this->localizationTree[$id] = array(
                'name' => $localization
            );
            $id++;
            
            foreach ($node as $key => $value) {
                $id = $this->buildTree($key, $value, $id);
            }
            
            $this->localizationTree[$nodeId]['sid'] = $id;
            
            return $id+1;
            
        } else {
            $this->localizationTree[$id] = array(
                'name' => $node,
                'sid' => $id+1
            );
            
            return $id+2;
        }
    }
    
    private function initLocalizationToIdMap() {
        foreach ($this->localizationTree as $id => $node) {
            $this->localizationToId[$node['name']] = $id;
        }
    }
    
    public function getIdByLocalization($localization) {
        if (isset($this->localizationToId[$localization])) {
            return $this->localizationToId[$localization];
        } else {
            throw new \InvalidArgumentException("Localization ('".$localization."') not found in tree");
        }
    }
    
    public function getSecondaryIdByLocalization($localization) {
        
    }
    
    public function getLocalizationById($id) {
        if (isset($this->localizationTree[$id])) {
            return $this->localizationTree[$id];
        } else {
           throw new \InvalidArgumentException("Given id ('".$id."') is not valid primary localization id"); 
        }
    }
}