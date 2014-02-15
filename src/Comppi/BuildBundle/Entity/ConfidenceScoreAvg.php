<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ConfidenceScoreAvg
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity="Protein")
     */
    protected $proteinId;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $calculatorId;

    /**
     * @ORM\Column(type="float")
     */
    protected $avgInteractionScore;
    
    /**
     * @ORM\Column(type="float")
     */
    protected $avgLocalizationScore;
}