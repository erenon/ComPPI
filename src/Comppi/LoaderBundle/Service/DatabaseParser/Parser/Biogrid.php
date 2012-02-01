<?php

namespace Comppi\LoaderBundle\Service\DatabaseParser\Parser;

class Biogrid extends AbstractParser implements ParserInterface
{
    protected $matching_files = array(
        'biogrid' => 'BiogridTest',
        'BIOGRID-ORGANISM-Caenorhabditis_elegans-3.1.81.tab2.txt' => 'BiogridCe',
        'BIOGRID-ORGANISM-Drosophila_melanogaster-3.1.81.tab2.txt' => 'BiogridDm',
    	'BIOGRID-ORGANISM-Homo_sapiens-3.1.81.tab2.txt' => 'BiogridHs',
    	'BIOGRID-ORGANISM-Saccharomyces_cerevisiae-3.1.81.tab2.txt' => 'BiogridSc'
    );
    
    protected $field_blacklist = array(
        '#BioGRID Interaction ID',
        'Entrez Gene Interactor A',
        'Entrez Gene Interactor B',
        'BioGRID ID Interactor A',
        'BioGRID ID Interactor B',
        'Experimental System Type',
        'Author',
        'Organism Interactor A',
        'Organism Interactor B',
        'Throughput',
        'Score',
        'Modification',
        'Phenotypes',
        'Qualifications',
        'Tags'        
    );
    
    public function getFieldArray($file_handle) {
        $first_line = fgets($file_handle);
        
        $fields = explode("\t", $first_line);
        
        $fields = $this->filterFieldArray($fields);
        $fields = $this->cleanFieldArray($fields);
        $fields = $this->camelizeFieldArray($fields);
        
        return $fields;
    }
    
    /**
     * @todo Make Experimental System Type index dynamic
     * @param array $record
     * @return true if record MUST NOT inserted to the db.
     */
    protected function isRecordFiltered(array $record) {
        // 12: index of Experimental System Type field
        if ($record[12] == 'genetic') {
            return true;
        }
        
        return false;
    }
}