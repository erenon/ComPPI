<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class UniprotDeleted extends AbstractMapParser
{
    protected $headerCount = 0;    // custom header

    protected static $parsableFileNames = array(
        'delac_sp.txt',
        'delac_tr.txt'
    );

    public function __construct($fileName) {
        parent::__construct($fileName);

        file_put_contents(STDERR, 'UniprotDeleted parser is deprecated and will be removed.' .
         ' Delete all delac_[sp|tr].txt sources as they are no longer needed.');
    }

    protected function dropHeader() {
        do {
            $line = $this->readLine();
            $isEndOfHeader = (substr($line, 0, 5) == '_____');
        } while ($isEndOfHeader == false && $line !== false);
    }

    protected function readRecord() {
        $line = $this->readLine();

        if ($line === false) {
            // EOF
            return;
        }

        if (substr($line, 0, 5) == '-----') {
            // footer reached, consume it
            while ($line) {
                $line = $this->readLine();
            }
            // line if false, EOF
            return;
        }

        $this->currentRecord = array(
            'namingConventionA' => 'UniProtKB-AC',
            'namingConventionB'	=> 'UniProtKB/Deleted',
            'proteinNameA'	=> $line,
            'proteinNameB'	=> $line
        );
    }
}