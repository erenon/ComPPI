<?php

namespace Comppi\BuildBundle\Test\Entity;

use Comppi\BuildBundle\Entity\ProteinNameMap;

class ProteinNameMapFactory extends ProteinNameMap
{
    public static function get(
        $specieId,
        $namingConventionA,
        $proteinNameA,
        $namingConventionB,
        $proteinNameB
    ) {
        $entity = new ProteinNameMap();
        $entity->specieId = $specieId;
        $entity->namingConventionA = $namingConventionA;
        $entity->proteinNameA = $proteinNameA;
        $entity->namingConventionB = $namingConventionB;
        $entity->proteinNameB = $proteinNameB;

        return $entity;
    }
}