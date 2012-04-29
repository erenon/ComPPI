<?php

namespace Comppi\BuildBundle\Service\LocalizationTranslator;

class LocalizationTranslator
{
    private $localizations = array();
    
    private $localizationToId = array();
    private $idToLocalization = array();
    
    public function __construct($localizationFile) {
        $this->loadLocalizations($localizationFile);
    }
    
    private function loadLocalizations($fileName) {
        $handle = fopen($fileName, 'r');
        
        if ($handle === false) {
            throw new \Exception("Failed to open localization tree file: '" . $fileName . "'");
        }
        
        $nextId = 0;
        $this->addLocalization('Protein', $nextId);
        $nextId++;
        
        $currentIdent = 0;
        
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            $lineParts = explode(' ', $line);
            $ident = strlen($lineParts[0]);
            $goCode = ltrim(rtrim(array_pop($lineParts), ")\n"), "(");
            
            if ($ident == $currentIdent) {
                $this->localizations[count($this->localizations) - 1]['sid'] = $nextId;
                $nextId++;
                
                $this->addLocalization($goCode, $nextId);
                $nextId++;
                
            } else if ($ident > $currentIdent) {
                assert($currentIdent + 1 == $ident);
                
                $this->addLocalization($goCode, $nextId);
                $nextId++;
                $currentIdent = $ident;
            } else {
                $unidentCount = $currentIdent - $ident + 1;
                
                for ($level = 0; $level < $unidentCount; $level++) {
                    
                    for ($i = count($this->localizations) - 1; $i >= 0; $i--) {
                        if ($this->localizations[$i]['sid'] === false) {
                            $this->localizations[$i]['sid'] = $nextId;
                            $nextId++;
                            
                            break;
                        }
                    }
                    
                }
                
                $this->addLocalization($goCode, $nextId);
                $nextId++;
                
                $currentIdent = $ident;
            }
        }
        
        // close last branch
        $unidentCount = $ident + 1;
        
        for ($level = 0; $level < $unidentCount; $level++) {
            
            for ($i = count($this->localizations) - 1; $i >= 0; $i--) {
                if ($this->localizations[$i]['sid'] === false) {
                    $this->localizations[$i]['sid'] = $nextId;
                    $nextId++;
                    
                    break;
                }
            }
            
        }
    }
    
    private function addLocalization($goCode, $id) {
        $this->localizations[] = array(
            'id' => $id,
            'name' => $goCode,
            'sid' => false
        );
        
        $this->localizationToId[$goCode] = $id;
        $this->idToLocalization[$id] = $goCode;
    }
    
    public function getIdByLocalization($localization) {
        if (isset($this->localizationToId[$localization])) {
            return $this->localizationToId[$localization];
        } else {
            throw new \InvalidArgumentException("Localization ('".$localization."') not found in tree");
        }
    }
    
    public function getSecondaryIdByLocalization($localization) {
        throw new \BadMethodCallException("getSecondaryIdByLocalization method not implemented");
    }
    
    public function getLocalizationById($id) {
        if (isset($this->idToLocalization[$id])) {
            return $this->idToLocalization[$id];
        } else {
           throw new \InvalidArgumentException("Given id ('".$id."') is not valid primary localization id"); 
        }
    }
}