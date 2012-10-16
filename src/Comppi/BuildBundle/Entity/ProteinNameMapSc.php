<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(indexes={@ORM\index(name="search_idx", columns={"namingConventionA", "proteinNameA"}), @ORM\index(name="reverse_search_idx", columns={"namingConventionB", "proteinNameB"})})
 */
class ProteinNameMapSc
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
    protected $namingConventionA;

    /**
     * @ORM\Column(type="string", length="255")
     */
    protected $proteinNameA;

    /**
     * @ORM\Column(type="string", length="255")
     */
    protected $namingConventionB;

    /**
     * @ORM\Column(type="string", length="255")
     */
    protected $proteinNameB;
}