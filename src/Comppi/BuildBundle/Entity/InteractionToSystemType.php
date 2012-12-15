<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(indexes={@ORM\index(name="search_idx", columns={"interactionId"})})
 */
class InteractionToSystemType
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
     * @ORM\ManyToOne(targetEntity="SystemType")
     */
    protected $systemTypeId;
}