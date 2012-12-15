<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(indexes={@ORM\index(name="search_idx", columns={"name"})})
 */
class SystemType
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length="255", unique="true")
     */
    protected $name;

    /**
     * @ORM\Column(type="integer")
     */
    protected $confidenceType;
}