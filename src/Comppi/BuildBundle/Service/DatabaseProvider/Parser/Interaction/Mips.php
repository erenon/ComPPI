<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Interaction;

class Mips extends AbstractInteractionParser
{
    protected static $parsableFileNames = array(
        'allppis.xml',
    );

    protected $databaseIdentifier = "MIPS";

    protected $headerCount = 0;

    protected $parser;

    const ST_START = 1;
    const ST_INTERACTION = 2;
    const ST_EXPERIMENT_DESCRIPTION = 3;
    const ST_BIBREF = 4;
    const ST_INTERACTION_DETECTION = 5;
    const ST_READ_EXPSYSTYPE = 6;
    const ST_PARTICIPANT_LIST = 7;
    const ST_PROTEIN_INTERACTOR = 8;
    const ST_XREF = 9;

    protected $status = self::ST_START;

    protected $interactionStack = array();
    protected $nextInteraction;

    public function __construct($fileName) {
        parent::__construct($fileName);

        $this->parser = xml_parser_create();
        // disable uppercasing. @see http://php.net/manual/en/xml.case-folding.php
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 0);
        xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');

        xml_set_element_handler(
            $this->parser,
            array($this, "startTagHandler"),
            array($this, "endTagHandler")
        );
        xml_set_character_data_handler(
            $this->parser,
            array($this, "cdataHandler")
        );
    }

    public function startTagHandler($parser, $name, $attributes) {
        switch ($name) {
            case 'interaction':
                if ($this->status == self::ST_START) {
                    $this->status = self::ST_INTERACTION;
                    $this->nextInteraction = array();
                }
                break;
            case 'experimentDescription':
                if ($this->status = self::ST_INTERACTION) {
                    $this->status = self::ST_EXPERIMENT_DESCRIPTION;
                }
                break;
            case 'bibref':
                if ($this->status == self::ST_EXPERIMENT_DESCRIPTION) {
                    $this->status = self::ST_BIBREF;
                }
                break;
            case 'primaryRef':
                if ($this->status == self::ST_BIBREF) {
                    $pubmedId = 0;
                    if (isset($attributes['id'])) {
                        if (!is_numeric($attributes['id'])) {
                            // multiple pubmed
                            $pubmedId = substr(
                                $attributes['id'],
                                0,
                                strpos($attributes['id'], ';')
                            );
                        } else {
                            $pubmedId = $attributes['id'];
                        }
                    } // else no pubmed specified
                    $this->nextInteraction['pubmedId'] = $pubmedId;
                } elseif ($this->status == self::ST_XREF) {
                    if (!isset($attributes['id'])) {
                        // participant not specified, drop interaction
                    	$this->unfilteredEntryCount++;
                        $this->status = self::ST_START;
                        break;
                    }

                    $interactor = $attributes['id'];
                    if (!isset($this->nextInteraction['proteinAName'])) {
                        $this->nextInteraction['proteinAName'] = $interactor;
                    } else {
                        $this->nextInteraction['proteinBName'] = $interactor;
                    }
                }
                break;
            case 'interactionDetection':
                if ($this->status == self::ST_EXPERIMENT_DESCRIPTION) {
                    $this->status = self::ST_INTERACTION_DETECTION;
                }
                break;
            case 'shortLabel':
                if ($this->status == self::ST_INTERACTION_DETECTION) {
                    $this->status = self::ST_READ_EXPSYSTYPE;
                }
                break;
            case 'participantList':
                if ($this->status == self::ST_INTERACTION) {
                    $this->status = self::ST_PARTICIPANT_LIST;
                }
                break;
            case 'proteinInteractor':
                if ($this->status == self::ST_PARTICIPANT_LIST) {
                    $this->status = self::ST_PROTEIN_INTERACTOR;
                }
                break;
            case 'xref':
                if ($this->status == self::ST_PROTEIN_INTERACTOR) {
                    $this->status = self::ST_XREF;
                }
                break;
            case 'organism':
                if ($this->status == self::ST_PROTEIN_INTERACTOR) {
                    $specieId = $attributes['ncbiTaxId'];

                    // 9606: Human taxonomy id
                    if ($specieId != '9606') {
                    	$this->unfilteredEntryCount++;
                        // interspecie interaction, drop record
                        $this->status = self::ST_START;
                    }
                }
                break;

        }
    }

    public function endTagHandler($parser, $name) {
        switch ($name) {
            case 'bibref':
                if ($this->status == self::ST_BIBREF) {
                    $this->status = self::ST_EXPERIMENT_DESCRIPTION;
                }
                break;
            case 'interactionDetection':
                if ($this->status == self::ST_INTERACTION_DETECTION) {
                    $this->status = self::ST_EXPERIMENT_DESCRIPTION;
                }
                break;
            case 'experimentDescription':
                if ($this->status == self::ST_EXPERIMENT_DESCRIPTION) {
                    $this->status = self::ST_INTERACTION;
                }
                break;
            case 'xref':
                if ($this->status == self::ST_XREF) {
                    $this->status = self::ST_PROTEIN_INTERACTOR;
                }
                break;
            case 'proteinInteractor':
                if ($this->status == self::ST_PROTEIN_INTERACTOR) {
                    $this->status = self::ST_PARTICIPANT_LIST;
                }
                break;
            case 'interaction':
                if ($this->status == self::ST_PARTICIPANT_LIST) { // record hasn't been dropped
                    $this->interactionStack[] = $this->nextInteraction;
                    $this->status = self::ST_START;
                }
                break;
        }
    }

    public function cdataHandler($parser, $data) {
        switch ($this->status) {
            case self::ST_READ_EXPSYSTYPE:
                $method = trim($data);

                // crop "coip:" from the beginning
                // 6: strlen('coip: ')
                if (substr($method, 0, 6) == 'coip: ') {
                    $method = substr($method, 6);
                }

                $this->nextInteraction['experimentalSystemType'] = $method;
                $this->status = self::ST_INTERACTION_DETECTION;

                break;
        }
    }

    protected function readInteraction() {
        while (count($this->interactionStack) == 0 && !feof($this->fileHandle)) {
            $data = fread($this->fileHandle, 4096);
            if (!xml_parse($this->parser, $data, false)) {
                printf("XML error: %s at line %d",
                        xml_error_string(xml_get_error_code($this->parser)),
                        xml_get_current_line_number($this->parser));
                throw new \Exception('Failed to parse Mips xml: ' . $this->fileName);
            }
        }
    }

    protected function readRecord() {
        while (count($this->interactionStack) == 0 && !feof($this->fileHandle)) {
            $this->readInteraction();
            $this->unfilteredEntryCount += count($this->interactionStack);
        }

        if (count($this->interactionStack) > 0) {
            $nextInteraction = array_shift($this->interactionStack);

            $this->currentRecord = $nextInteraction;
            $this->currentRecord['proteinANamingConvention'] = 'UniProtKB-AC';
            $this->currentRecord['proteinBNamingConvention'] = 'UniProtKB-AC';
        }
    }

    public function valid() {
        $valid = !feof($this->fileHandle);
        if (!$valid) {
            xml_parser_free($this->parser);
            fclose($this->fileHandle);
        }

        return $valid;
    }
}