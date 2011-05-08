<?php

namespace Comppi\LoaderBundle\Tests\Service\EntityGenerator;

use Comppi\LoaderBundle\Service\EntityGenerator\EntityGenerator;

require_once 'vfsStream/vfsStream.php';
use vfsStream;
use vfsStreamWrapper;
use vfsStreamDirectory;

class EntityGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp() {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(
            new vfsStreamDirectory('templateDir')  
        );
    }
    
    /**
     * @expectedException \UnexpectedValueException
     */
    public function testMalformedTemplate()
    {
        file_put_contents(
            vfsStream::url('templateDir') . '/EntityTest.tpl',
            'foobar {% GENERAL FIELD SEPARATOR %} only two parts'
        );
        
        $generator = new EntityGenerator(vfsStream::url('templateDir') . '/EntityTest.tpl');
    }
    
    public function testGenerate()
    {
        file_put_contents(
            vfsStream::url('templateDir') . '/EntityTest.tpl',
            '{ENTITY_NAME}--{% GENERAL FIELD SEPARATOR %}{FIELD_NAME},{% GENERAL FIELD SEPARATOR %}--end'
        );
        
        $generator = new EntityGenerator(vfsStream::url('templateDir') . '/EntityTest.tpl');
        $content = $generator->generate('name', array('foo', 'bar lol'));
        
        $this->assertEquals(
            'Name--foo,bar_lol,--end',
            $content
        );
    }
}