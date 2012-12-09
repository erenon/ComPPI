<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ConfidenceScore
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity="Interaction")
     */
    protected $interactionId;

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