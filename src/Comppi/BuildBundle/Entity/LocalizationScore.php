<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class LocalizationScore
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity="ProteinToLocalization")
     */
    protected $localizationId;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $calculatorId;

    /**
     * @ORM\Column(type="float")
     */
    protected $score;
}