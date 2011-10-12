<?php

namespace Comppi\LoaderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class {ENTITY_NAME}
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    {% GENERAL FIELD SEPARATOR %}
    /**
     * @ORM\Column({FIELD_TYPE})
     */
    protected ${FIELD_NAME};
    {% GENERAL FIELD SEPARATOR %}
}