<?php

namespace Comppi\LoaderBundle\Entity;

/**
 * @orm:Entity
 */
class {ENTITY_NAME}
{
    /**
     * @orm:Id
     * @orm:Column(type="integer")
     * @orm:GeneratedValue(strategy="AUTO")
     */
    protected $id;

    {% GENERAL FIELD SEPARATOR %}
    /**
     * @orm:Column(type="string", length="255")
     */
    protected ${FIELD_NAME};
    {% GENERAL FIELD SEPARATOR %}
}