<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ProteinToLocalizationHs
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

    /**
     * @ORM\Column(type="string", length="255")
     */
    protected $experimentalSystemType;
}