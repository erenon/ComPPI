<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     indexes={
 *         @ORM\index(name="search_idx", columns={"proteinName", "proteinNamingConvention"}),
 *         @ORM\index(name="species_idx", columns={"specieId"})
 *     }
 * )
 */
class Protein
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
    protected $proteinName;

    /**
     * @ORM\Column(type="string", length="255")
     */
    protected $proteinNamingConvention;
}