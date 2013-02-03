<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Map;

class Biogrid extends AbstractMapParser
{
    protected static $parsableFileNames = array(
        'BIOGRID-IDENTIFIERS-3.1.92.tab.txt',
    );

    private $records = array();
    private $recordBuffer;
    private $lastBiogridIdRead = 0;

    /**
     * Accepted naming conventions
     */
    private $acceptedConventions = array(
        'SWISSPROT' => 'UniProtKB-AC',
        'ENSEMBL' => 'EnsemblGeneId',
        'REFSEQ_PROTEIN_ACCESSION' => 'refseq',
        'WORMBASE' => 'WBGeneId'
    );

    /**
     * Precedence order of naming conventions.
     * Strongest first.
     * @var array
     */
    private $namingConventionOrder = array(
        'UniProtKB-AC',
        'EnsemblGeneId',
        'refseq',
        'WBGeneId'
    );

    protected function readRecord() {
        while (empty($this->records) && !feof($this->fileHandle)) {
            $line = $this->readLine();
            if ($line === false) {
                // EOF
                $this->commitBuffer();
                continue;
            }

            $recordArray = explode("\t", $line);
            $this->checkRecordFieldCount($recordArray, 4);

            if ($recordArray[0] != $this->lastBiogridIdRead) {
                $this->commitBuffer();
                $this->lastBiogridIdRead = $recordArray[0];
            }

            $convention = $this->getConventionName($recordArray[2]);

            if ($convention) {
                $this->recordBuffer[] = array(
                    'namingConventionA' => $convention,
                    'proteinNameA' => $recordArray[1]
                );
            }
        }

        if (empty($this->records) == false) {
            $record = array_shift($this->records);

            $this->currentRecord = $record;
        }
    }

    private function commitBuffer() {
        if (count($this->recordBuffer) < 2) {
            // a translation should have a source and a target
            $this->recordBuffer = array();
            return;
        }

        // find stronges convention available for the current biogridId
        $strongestOrder = count($this->namingConventionOrder);
        $strongestKey = 0;

        foreach ($this->recordBuffer as $key => $record) {
            $order = array_search(
                $record['namingConventionA'],
                $this->namingConventionOrder
            );

            if ($order < $strongestOrder) {
                // stronger convention found
                $strongestOrder = $order;
                $strongestKey = $key;
            }
        }

        // remove strongest convention
        // and inject to the others as convention B

        $conventionB = array(
            'namingConventionB' => $this->recordBuffer[$strongestKey]['namingConventionA'],
            'proteinNameB' => $this->recordBuffer[$strongestKey]['proteinNameA']
        );

        unset($this->recordBuffer[$strongestKey]);

        foreach ($this->recordBuffer as $key => $record) {
            // remove the other same strong conventions
            // we don't want to provide conventionA -> conventionA style mapping here
            if ($record['namingConventionA'] == $conventionB['namingConventionB']) {
                unset($this->recordBuffer[$key]);
            } else {
                $this->recordBuffer[$key] = array_merge($record, $conventionB);
            }
        }

        $this->records = $this->recordBuffer;
        $this->recordBuffer = array();
    }

    private function getConventionName($originalName) {
        if (isset($this->acceptedConventions[$originalName])) {
            return $this->acceptedConventions[$originalName];
        } else {
            return false;
        }
    }

    /* Iterator methods */

    public function valid() {
        $valid = !feof($this->fileHandle) || !empty($this->records);
        if (!$valid) {
            fclose($this->fileHandle);
        }

        return $valid;
    }
}