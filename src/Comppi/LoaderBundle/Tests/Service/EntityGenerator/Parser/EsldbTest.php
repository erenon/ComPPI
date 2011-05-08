<?php

namespace Comppi\LoaderBundle\Tests\Service\EntityGenerator\Parser;

use Comppi\LoaderBundle\Service\EntityGenerator\Parser\Esldb;

require_once 'vfsStream/vfsStream.php';
use vfsStream;
use vfsStreamWrapper;
use vfsStreamDirectory;

class EsldbParserTest extends \PHPUnit_Framework_TestCase
{
    public function setUp() {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(
            new vfsStreamDirectory('databaseDir')  
        );    
    }
    
    public function testFieldParsing() {
        $file = vfsStream::url('databaseDir') . '/esldbTestData';
        file_put_contents(
            $file,
            "foo\tbar\tbaz\nrofl\tcopter\nother\nlines"
        );
        
        $file_handle = fopen($file, 'r');
        
        $parser = new Esldb();
        
        $this->assertEquals(
            array('foo', 'bar', 'baz'),
            $parser->getFieldArray($file_handle)
        );
    }
}