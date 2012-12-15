<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(indexes={@ORM\index(name="search_idx", columns={"protLocId"})})
 */
class ProtLocToSystemType
{
	/**
	 * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity="ProteinToLocalization")
     */
    protected $protLocId;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity="SystemType")
     */
    protected $systemTypeId;
}