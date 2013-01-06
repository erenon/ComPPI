<?php

namespace Comppi\BuildBundle\Test\Service\ProteinTranslator;

use Comppi\BuildBundle\Service\ProteinTranslator\ProteinTranslator;

class ProteinTranslatorSynonymMock extends ProteinTranslator
{
    protected function getWeakerSynonyms($namingConvention, $proteinName, $specieId) {
        if ($namingConvention == 'A') {
            return array(
                array(
                    'convention' => 'D',
                    'name' => $proteinName
                ),
                array(
                    'convention' => 'E',
                    'name' => $proteinName
                ),
                array(
                    'convention' => 'C',
                    'name' => $proteinName
                )
            );
        } else if ($namingConvention == 'B' || $namingConvention == 'C') {
            return array(
                array(
                    'convention' => 'D',
                    'name' => $proteinName
                )
            );
        } else if ($namingConvention == 'D') {
            return array(
                array(
                    'convention' => 'E',
                    'name' => $proteinName
                ),
                array(
                    'convention' => 'F',
                    'name' => $proteinName
                )
            );
        }

        return array();
    }

    protected function getStrongerSynonyms($namingConvention, $proteinName, $specieId) {
        if ($namingConvention == 'E' || $namingConvention == 'F') {
            return array(
                array(
                    'convention' => 'D',
                    'name' => $proteinName
                )
            );
        } else if ($namingConvention == 'D') {
            return array(
                array(
                    'convention' => 'A',
                    'name' => $proteinName
                ),
                array(
                    'convention' => 'B',
                    'name' => $proteinName
                ),
                array(
                    'convention' => 'B',
                    'name' => $proteinName
                ),
                array(
                    'convention' => 'C',
                    'name' => $proteinName
                )
            );
        }

        return array();
    }
}