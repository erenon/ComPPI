<?php

namespace Comppi\LoaderBundle\Tests\Service\DatabaseParser\Parser;

use Comppi\LoaderBundle\Service\DatabaseParser\Parser\Biogrid;

require_once 'vfsStream/vfsStream.php';
use vfsStream;
use vfsStreamWrapper;
use vfsStreamDirectory;

class BiogridParserTest extends \PHPUnit_Framework_TestCase
{
    public function setUp() {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(
            new vfsStreamDirectory('databaseDir')  
        );    
    }
    
    public function testFieldParsing() {
        $file = vfsStream::url('databaseDir') . '/biogridTestData';
        file_put_contents(
            $file,
            "#foo\tbar\tbaz\nrofl\tcopter\nother\nlines"
        );
        
        $file_handle = fopen($file, 'r');
        
        $parser = new Biogrid();
        
        $this->assertEquals(
            array('foo', 'bar', 'baz'),
            $parser->getFieldArray($file_handle)
        );
    }
}