<?php

namespace Comppi\LoaderBundle\Tests\Service\DatabaseParser\Parser;

use Comppi\LoaderBundle\Service\DatabaseParser\Parser\Pagosub;

require_once 'vfsStream/vfsStream.php';
use vfsStream;
use vfsStreamWrapper;
use vfsStreamDirectory;

class PagosubParserTest extends \PHPUnit_Framework_TestCase
{
    public function setUp() {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(
            new vfsStreamDirectory('databaseDir')  
        );    
    }
    
    public function testFieldParsing() {
        $file = vfsStream::url('databaseDir') . '/pagosubTestData';
        file_put_contents(
            $file,
            "header trash\nfoo (braces), bar, baz\nrofl\tcopter\nother\nlines"
        );
        
        $file_handle = fopen($file, 'r');
        
        $parser = new Pagosub();
        
        $this->assertEquals(
            array('foo', 'bar', 'baz'),
            $parser->getFieldArray($file_handle)
        );
    }
}