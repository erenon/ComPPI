<?php

namespace Comppi\BuildBundle\Test\Service\ProteinTranslator;

class ProteinTranslatorTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSynonyms() {
        $em = $this->getMock('\Doctrine\ORM\EntityManager', array('getConnection'), array(), '', false);
        $translator = new ProteinTranslatorSynonymMock($em);

        $synonyms = $translator->getSynonyms('A', 'foo', 0);

        $expectedConventions = array('B', 'B', 'C', 'D', 'E', 'F');

        $this->assertCount(count($expectedConventions), $synonyms);

        foreach ($synonyms as $synonym) {
            $this->assertContains(
                $synonym['convention'],
                $expectedConventions
            );
        }
    }
}