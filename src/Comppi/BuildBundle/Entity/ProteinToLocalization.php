<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     indexes={
 *         @ORM\index(name="search_idx", columns={"proteinId"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="single_loc_per_source", columns={"proteinId", "localizationId", "sourceDb"})
 *     }
 * )
 */
class ProteinToLocalization
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity="ProteinCe")
     */
    protected $proteinId;

    /**
     * @ORM\Column(type="integer")
     */
    protected $localizationId;

    /**
     * @ORM\Column(type="string", length="255")
     */
    protected $sourceDb;

    /**
     * @ORM\Column(type="integer")
     */
    protected $pubmedId;
}