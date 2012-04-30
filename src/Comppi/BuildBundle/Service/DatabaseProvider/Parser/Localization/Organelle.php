<?php

namespace Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization;

class Organelle extends AbstractLocalizationParser
{
    protected static $parsableFileNames = array(
        'organelle_ce.txt',
        'organelle_dm.txt',
        'organelle_hs.txt',
        'organelle_sc.txt'
    );

    protected $localizationToGoCode = array (
        'axoneme' => 'GO:0005930',
        'basal body' => 'GO:0005932',
        'bud neck' => 'GO:0005935',
        'bud tip' => 'GO:0005934',
        'cell septum' => 'GO:0030428',
        'chromosome' => 'GO:0005694',
        'cilium' => 'GO:0005929',
        'contractile ring' => 'GO:0070938',
        'cyclin-dependent protein kinase holoenzyme complex' => 'GO:0000307',
        'cytoskeleton' => 'GO:0005856',
        'cytosol' => 'GO:0005737',
        'endomembrane system' => 'GO:0012505',
        'endopeptidase Clp complex' => 'GO:0009368',
        'endoplasmic reticulum' => 'GO:0005783',
        'exocyst' => 'GO:0000145',
        'exosome (RNase complex)' => 'GO:0000178',
        'extrinsic to endoplasmic reticulum membrane' => 'GO:0042406',
        'extrinsic to vacuolar membrane' => 'GO:0000306',
        'flagellum' => 'GO:0019861',
        'incipient bud site' => 'GO:0000131',
        'inclusion body' => 'GO:0016234',
        'integral to endoplasmic reticulum membrane' => 'GO:0030176',
        'integral to Golgi membrane' => 'GO:0030173',
        'integral to nuclear inner membrane' => 'GO:0005639',
        'integral to synaptic vesicle membrane' => 'GO:0030285',
        'intracellular cyclic nucleotide activated cation channel complex' => 'GO:0017071',
        'lipopolysaccharide receptor complex' => 'GO:0046696',
        'lysosomal membrane' => 'GO:0005765',
        'mitochondrion' => 'GO:0005739',
        'mRNA cap complex' => 'GO:0031533',
        'nucleoid' => 'GO:0009295',
        'nucleus' => 'GO:0005634',
        'Organelle' => 'GO:0043226',
        'organelle inner membrane' => 'GO:0019866',
        'oxoglutarate dehydrogenase complex' => 'GO:0045252',
        'peroxisomal membrane' => 'GO:0005778',
        'plasma membrane' => 'GO:0005886',
        'polarisome' => 'GO:0000133',
        'pore complex' => 'GO:0046930',
        'proton-transporting ATP synthase complex' => 'GO:0045259',
        'proton-transporting two-sector ATPase complex' => 'GO:0016469',
        'respiratory chain complex I' => 'GO:0045271',
        'respiratory chain complex II' => 'GO:0045273',
        'respirator chain complex III' => 'GO:0045275',
        'respiratory chain complex IV' => 'GO:0045277',
        'ribonucleoprotein complex' => 'GO:0030529',
        'RNA polymerase complex' => 'GO:0030880',
        'septin ring' => 'GO:0005940',
        'succinate dehydrogenase complex (ubiquinone)' => 'GO:0045281',
        'ubiquitin ligase complex' => 'GO:0000151',
        'UDP-N-acetylglucosamine-peptide N-acetylglucosaminyltransferase complex' => 'GO:0017122',
        'vacuolar membrane' => 'GO:0005774'
    );

    // has double header
    protected $hasHeader = false;

    protected function readRecord() {
        $line = $this->readLine();
        if ($line === false) {
            // EOF
            return;
        }

        $recordArray = explode("\t", $line);

        $this->currentRecord = array(
            'proteinId' => $recordArray[2],
            'namingConvention' => 'EnsemblPeptideId',
            'localization' => $this->getGoCodeByLocalizationName($recordArray[0]),
            'pubmedId' => 15608270,
            'experimentalSystemType' => 'Experimental (experimental)'
        );
    }

    /**
     * Redefine rewind, must drop double header
     * (non-PHPdoc)
     * @see Comppi\BuildBundle\Service\DatabaseProvider\Parser\Localization.AbstractLocalizationParser::rewind()
     */
    public function rewind() {
        if ($this->fileHandle == null) {
            $this->fileHandle = fopen($this->fileName, 'r');
            $this->currentIdx = 0;
        } else {
            rewind($this->fileHandle);
        }

        // drop double header
        fgets($this->fileHandle);
        fgets($this->fileHandle);

        $this->readRecord();
    }
}