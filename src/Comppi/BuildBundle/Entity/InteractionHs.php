<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(indexes={@ORM\index(name="search_idx_aid", columns={"actorAId"}), @ORM\index(name="search_idx_bid", columns={"actorBId"})})
 */
class InteractionHs
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

	/**
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity="ProteinHs")
     */
    protected $actorAId;

    /**
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity="ProteinHs")
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

    /**
     * @ORM\Column(type="string", length="255")
     */
    protected $experimentalSystemType;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isExperimental;

    /**
     * @ORM\Column(type="float")
     */
    protected $confidenceScore;
}