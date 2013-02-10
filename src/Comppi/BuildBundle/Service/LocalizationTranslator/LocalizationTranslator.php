<?php

namespace Comppi\BuildBundle\Service\LocalizationTranslator;

use Symfony\Component\Yaml\Parser;

class LocalizationTranslator
{
    public $localizations = array();

    private $idToIndex = array();
    private $localizationToIndex = array();

    private $localizationTree = null;

    private $largelocs;
    private $idToLargeloc = array();

    public function __construct($localizationFile, $largelocFile) {
        $this->loadLocalizations($localizationFile);
        $this->loadLargelocs($largelocFile);
    }

    private function loadLocalizations($fileName) {
        $handle = fopen($fileName, 'r');
        $lineIndex = 0;

        if ($handle === false) {
            throw new \Exception("Failed to open localization tree file: '" . $fileName . "'");
        }

        $nextId = 0;
        $this->addLocalization('Cell', $nextId, 'Cell');
        $nextId++;

        $currentIdent = 0;

        while (($line = fgets($handle)) !== false) {
            $lineIndex++;
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
                if ($currentIdent + 1 != $ident) {
                    throw new \InvalidArgumentException('Invalid ident in loc tree at line: ' . $lineIndex);
                }

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

    private function loadLargelocs($fileName) {
        $yamlParser = new Parser();
        $definition = $yamlParser->parse(file_get_contents($fileName));

        foreach ($definition['largelocs'] as $largelocName => $terms) {
            $ids = array();

            if (is_array($terms['childrenExcluded'])) {
                foreach ($terms['childrenExcluded'] as $singleTerm) {
                    $ids[] = $this->getIdByLocalization($singleTerm);
                }
            }

            if (is_array($terms['childrenIncluded'])) {
                foreach ($terms['childrenIncluded'] as $multiTerm) {
                    $id = $this->getIdByLocalization($multiTerm);
                    $sid = $this->getSecondaryIdByLocalization($multiTerm);

                    foreach ($this->idToIndex as $primaryId => $index) {
                        if ($id <= $primaryId && $primaryId < $sid) {
                            $ids[] = $primaryId;
                        }
                    }
                }
            }

            $this->largelocs[$largelocName] = $ids;
        }

        $this->loadIdToLargeloc();
    }

    private function loadIdToLargeloc() {
        foreach ($this->idToIndex as $id => $index) {
            // search for largeloc
            $largelocFound = false;

            foreach ($this->largelocs as $largeloc => $ids) {
                if (array_search($id, $ids) !== false) {
                    $this->idToLargeloc[$id] = $largeloc;
                    $largelocFound = true;
                    break;
                }
            }

            if ($largelocFound === false) {
                $this->idToLargeloc[$id] = false;
            }
        }
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

    public function getLocalizationTree() {
        if ($this->localizationTree == null) {
            $this->localizationTree = $this->getBranchForId(0);
        }

        return $this->localizationTree;
    }

    public function getLargelocs() {
        return $this->largelocs;
    }

    public function getLargelocById($id) {
        if (isset($this->idToLargeloc[$id])) {
            return $this->idToLargeloc[$id];
        } else {
            throw new \InvalidArgumentException("Given id ('".$id."') is not valid primary localization id");
        }
    }

    /**
     * This is an ugly hack used by getBranchForId
     * to persist state of iteration.
     *
     * @var int
     */
    private $maxBranchIndex;

    private function getBranchForId($id) {
        $this->maxBranchIndex = 0;
        $children = array();

        $index = $this->idToIndex[$id];
        $rootSid = $this->localizations[$index]['sid'];
        for ($i = $index + 1; $i < count($this->localizations) && $this->localizations[$i]['sid'] < $rootSid; $i++) {
            $loc = $this->localizations[$i];

            $child = $loc;

            if ($loc['id'] + 1 != $loc['sid']) {
                $child['children'] = $this->getBranchForId($loc['id']);

                // skip children
                $i = $this->maxBranchIndex;
            }

            if ($this->maxBranchIndex < $i) {
                $this->maxBranchIndex = $i;
            }

            $children[] = $child;
        }

        return $children;
    }
}