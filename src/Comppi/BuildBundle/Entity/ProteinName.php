<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="unique_name", columns={"name"})
 *     }
 * )
 */
class ProteinName
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length="255")
     */
    protected $name;
}