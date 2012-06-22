<?php

namespace Comppi\BuildBundle\Service\LocalizationTranslator;

class LocalizationTranslator
{
    private $localizations = array();

    private $idToIndex = array();
    private $localizationToIndex = array();

    public function __construct($localizationFile) {
        $this->loadLocalizations($localizationFile);
    }

    private function loadLocalizations($fileName) {
        $handle = fopen($fileName, 'r');

        if ($handle === false) {
            throw new \Exception("Failed to open localization tree file: '" . $fileName . "'");
        }

        $nextId = 0;
        $this->addLocalization('Cell', $nextId, 'Cell');
        $nextId++;

        $currentIdent = 0;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $ident = strpos($line, ' ');
            $humanReadableLen = strrpos($line, ' ') - $ident - 1;
            $humanReadable = substr($line, $ident + 1, $humanReadableLen);
            $goCode = substr($line, $ident + $humanReadableLen + 3, -1);

            if ($ident == $currentIdent) {
                $this->localizations[count($this->localizations) - 1]['sid'] = $nextId;
                $nextId++;

                $this->addLocalization($goCode, $nextId, $humanReadable);
                $nextId++;

            } else if ($ident > $currentIdent) {
                assert($currentIdent + 1 == $ident);

                $this->addLocalization($goCode, $nextId, $humanReadable);
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

                $this->addLocalization($goCode, $nextId, $humanReadable);
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

    private function addLocalization($goCode, $id, $humanReadable) {
        $index = count($this->localizations);

        $this->localizations[] = array(
            'id' => $id,
            'name' => $goCode,
            'sid' => false,
            'humanReadable' => $humanReadable
        );

        $this->idToIndex[$id] = $index;
        $this->localizationToIndex[$goCode] = $index;
    }

    public function getIdByLocalization($localization) {
        if (isset($this->localizationToIndex[$localization])) {
            $index = $this->localizationToIndex[$localization];
            return $this->localizations[$index]['id'];
        } else {
            throw new \InvalidArgumentException("Localization ('".$localization."') not found in tree");
        }
    }

    public function getSecondaryIdByLocalization($localization) {
        if (isset($this->localizationToIndex[$localization])) {
            $index = $this->localizationToIndex[$localization];
            return $this->localizations[$index]['sid'];
        } else {
            throw new \InvalidArgumentException("Localization ('".$localization."') not found in tree");
        }
    }

    public function getLocalizationById($id) {
        if (isset($this->idToIndex[$id])) {
            $index = $this->idToIndex[$id];
            return $this->localizations[$index]['name'];
        } else {
           throw new \InvalidArgumentException("Given id ('".$id."') is not valid primary localization id");
        }
    }

    public function getHumanReadableLocalizationById($id) {
        if (isset($this->idToIndex[$id])) {
            $index = $this->idToIndex[$id];
            return $this->localizations[$index]['humanReadable'];
        } else {
           throw new \InvalidArgumentException("Given id ('".$id."') is not valid primary localization id");
        }
    }
}