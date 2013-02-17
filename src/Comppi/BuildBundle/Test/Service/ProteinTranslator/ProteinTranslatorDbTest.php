<?php

namespace Comppi\BuildBundle\Test\Service\ProteinTranslator;

use Comppi\BuildBundle\Test\Entity\ProteinNameMapFactory;

use Comppi\BuildBundle\Entity\ProteinNameMap;

use Comppi\BuildBundle\Test\Common\KernelAwareTest;

class ProteinTranslatorDbTest extends KernelAwareTest
{
    /**
     * @var Comppi\BuildBundle\Service\ProteinTranslator\ProteinTranslator
     */
    protected $translator;

    public function setUp() {
        parent::setUp();

        $this->translator = $this->container->get('comppi.build.proteinTranslator');
    }

    public function testGetComppiIds() {
        // load fixtures
        $em = $this->em;
        $em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            $mapEntries = array(
                ProteinNameMapFactory::get(0, 'conventionA', 'nameA1', 'UniProtKB-AC', 'nameB1'),
                ProteinNameMapFactory::get(0, 'conventionA', 'nameA1', 'UniProtKB-AC', 'nameB2'),
                ProteinNameMapFactory::get(0, 'conventionA', 'nameA1', 'UniProtKB-AC', 'nameB3'),
                ProteinNameMapFactory::get(0, 'conventionA', 'nameA2', 'UniProtKB-AC', 'nameB4'),
                ProteinNameMapFactory::get(0, 'conventionA', 'nameA2', 'UniProtKB-AC', 'nameB5')
            );

            foreach ($mapEntries as $entry) {
                $em->persist($entry);
            }

            $em->flush();
            $em->getConnection()->commit();
        } catch (Exception $e) {
            $em->getConnection()->rollback();
            $em->close();
            throw $e;
        }

        // test nameA1

        $expectedTranslation = array(1,2,3);
        $actualTranslation = $this->translator->getComppiIds('conventionA', 'nameA1', 0);

        $this->assertEquals($expectedTranslation, $actualTranslation);

        // test nameA2

        $expectedTranslationB = array(4,5);
        $actualTranslationB = $this->translator->getComppiIds('conventionA', 'nameA2', 0);

        $this->assertEquals($expectedTranslationB, $actualTranslationB);

        // test cache
        // cache shouldn't use db, discard db.
        $this->dropSchema();

        $actualTranslation = $this->translator->getComppiIds('conventionA', 'nameA1', 0);
        $this->assertEquals($expectedTranslation, $actualTranslation);

    }
}