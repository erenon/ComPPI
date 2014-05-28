<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

use Comppi\BuildBundle\Service\DatabaseProvider\Parser\AbstractParser;

abstract class AbstractLocalizationParser
    extends AbstractParser
    implements LocalizationParserInterface
{
    protected $localizationToGoCode = array();

    protected function getGoCodeByLocalizationName($localization) {
        if (isset($this->localizationToGoCode[$localization])) {
            return $this->localizationToGoCode[$localization];
        } else {
            //throw new \InvalidArgumentException("No GO code found for localization: '" . $localization . "'");
            echo "No GO code found for localization: '" . $localization . "'";
            return "GO:INVALID";
        }
    }
}