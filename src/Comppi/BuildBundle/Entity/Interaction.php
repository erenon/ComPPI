<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 * 		indexes={
 * 			@ORM\index(name="search_idx_aid", columns={"actorAId"}),
 * 			@ORM\index(name="search_idx_bid", columns={"actorBId"})
 * 		},
 * 		uniqueConstraints={
 * 			@ORM\UniqueConstraint(name="single_interaction_per_source", columns={"actorAId", "actorBId", "sourceDb"})
 * 		}
 * )
 */
class Interaction
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

	/**
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity="Protein")
     */
    protected $actorAId;

    /**
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity="Protein")
     */
    protected $actorBId;

    /**
     * @ORM\Column(type="string", length="255")
     */
    protected $sourceDb;

    /**
     * @ORM\Column(type="integer")
     */
    protected $pubmedId;
}