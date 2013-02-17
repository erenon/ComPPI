<?php

namespace Comppi\BuildBundle\Service\GexfWriter;

class GexfWriter
{
    const ST_INIT = 0;
    const ST_HEAD = 1;
    const ST_NODES = 2;
    const ST_EDGES = 3;

    private $state = self::ST_INIT;
    private $outputHandle;

    public function open($outputFile) {
        if ($this->state !== self::ST_INIT) {
            throw new \LogicException("Calling open() in invalid state");
        }

        $this->state = self::ST_HEAD;
        $this->outputHandle = fopen($outputFile, 'w');

        fwrite(
            $this->outputHandle,
            '<?xml version="1.0" encoding="UTF-8"?>
			 <gexf xmlns="http://www.gexf.net/1.2draft"
			 	xmlns:viz="http://www.gexf.net/1.1draft/viz"
				xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
				xsi:schemaLocation="http://www.gexf.net/1.2draft http://www.gexf.net/1.2draft/gexf.xsd"
				version="1.2"
			>
  			<graph mode="static">
    	');
    }

    public function nodes() {
        if ($this->state !== self::ST_HEAD) {
            throw new \LogicException("Invalid call of nodes(). Nodes must follow the header immediately.");
        }

        $this->state = self::ST_NODES;
        fwrite($this->outputHandle, '<nodes>');
    }

    public function addNode($id, $label) {
        if ($this->state !== self::ST_NODES) {
            throw new \LogicException("Invalid call of addNode(). It must follow the nodes() call.");
        }

        fwrite(
            $this->outputHandle,
            '<node id="' . $id . '" label="' . $label . '"/>'
        );
    }

    public function edges() {
        if ($this->state !== self::ST_NODES) {
            throw new \LogicException("Invalid call of edges(). Edges must follow the nodes immediately.");
        }

        $this->state = self::ST_EDGES;
        fwrite($this->outputHandle, '</nodes><edges>');
    }

    public function addEdge($from, $to) {
        if ($this->state !== self::ST_EDGES) {
            throw new \LogicException("Invalid call of addEdge(). It must follow the edges() call.");
        }

        fwrite(
            $this->outputHandle,
            '<edge source="' . $from . '" target="' . $to . '"/>'
        );
    }

    public function close() {
        if ($this->state !== self::ST_EDGES) {
            throw new \LogicException("Invalid call of close(). It must follow the edges() call.");
        }

        fwrite($this->outputHandle, '</edges></graph></gexf>');
        fclose($this->outputHandle);
        $this->state = self::ST_INIT;
    }
}