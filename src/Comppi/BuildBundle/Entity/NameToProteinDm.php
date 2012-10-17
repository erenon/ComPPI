<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(indexes={@ORM\index(name="search_idx", columns={"name", "namingConvention"})})
 */
class NameToProteinDm
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length="255")
     */
    protected $namingConvention;

    /**
     * @ORM\Column(type="string", length="255")
     */
    protected $name;

    /**
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity="ProteinDm")
     */
    protected $proteinId;

}