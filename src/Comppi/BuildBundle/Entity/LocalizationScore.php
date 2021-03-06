<?php

namespace Comppi\BuildBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class LocalizationScore
{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\ManyToOne(targetEntity="Protein")
	 */
	protected $proteinId;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $calculatorId;
    
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $majorLocName;

    /**
     * @ORM\Column(type="float")
     */
    protected $score;
}