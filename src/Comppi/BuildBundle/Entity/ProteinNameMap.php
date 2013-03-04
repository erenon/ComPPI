<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     indexes={
 *         @ORM\index(name="search_idx", columns={"proteinNameA", "namingConventionA", "specieId"}),
 *         @ORM\index(name="reverse_search_idx", columns={"proteinNameB", "namingConventionB", "specieId"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="unique_translation", columns={"namingConventionA", "proteinNameA", "namingConventionB", "proteinNameB"})
 *     }
 * )
 */
class ProteinNameMap
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $specieId;

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