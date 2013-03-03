<?php

namespace Comppi\BuildBundle\Test\Service\SystemTypeTranslator;

use Comppi\BuildBundle\Test\Common\KernelAwareTest;

class SystemTypeTranslatorTest extends KernelAwareTest
{
    /**
     * @var Comppi\BuildBundle\Service\SystemTypeTranslator\SystemTypeTranslator
     */
    protected $translator;

    public function setUp() {
        parent::setUp();

        $this->translator = $this->container->get('comppi.build.systemTypeTranslator');
    }

    public function testGetSystemTypeId() {
        $expectedId = $this->translator->getSystemTypeId('SVM decision tree');
        $actualId = $this->translator->getSystemTypeId('SVM decision tree (predicted)');

        $this->assertEquals($expectedId, $actualId);
    }
}