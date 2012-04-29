<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ProteinToDatabaseDm
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity="ProteinDm")
     */
    protected $proteinId;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length="255")
     */
    protected $sourceDb;
}