<?php

namespace Comppi\ProteinSearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class ProteinSearchController extends Controller
{
    // @TODO: implement a central input processing bundle/service + Symfony-style forms
	private $species_map = array(
		'Hs' => array('form_field' => 'fProtSearchHs', 'needed' => false),
		'Dm' => array('form_field' => 'fProtSearchDm', 'needed' => false),
		'Ce' => array('form_field' => 'fProtSearchCe', 'needed' => false),
		'Sc' => array('form_field' => 'fProtSearchSc', 'needed' => false)
	);
	private $verbose = false;
	//private $null_loc_needed = false; // show rows where one or both locations are unknown
	private $search_range_start = 0; // search result display starts from here
	private $search_result_per_page = 50; // limit the number of displayed results to ... (0 = unlimited)
	
	public function proteinSearchAction($protein_name = '')
    {
        $T = array(
			'verbose_log' => '',
            'ls' => array(),
			'keyword' => '',
			'protein_names' => '',
			'result_msg' => ''
        );

		$request = $this->getRequest();
		if ($request->getMethod() == 'POST') {
			$DB = $this->get('database_connection');

			$this->mapSpeciesRequest();
			$sp = $this->getRequestedSpecies();
			$requested_keyword = $_POST['fProtSearchKeyword']; // $request->request->get('fProtSearchKeyword') is not even NULL if empty!
			
			if (empty($sp))//$this->get('session')->setFlash('notice', 'Please select at least one genus!');
				throw new \ErrorException('Species missing!');
			if (empty($requested_keyword))
				throw new \ErrorException('Keyword missing!');

			if (!empty($requested_keyword) and !empty($sp)) {
				$T['keyword']  = htmlspecialchars(strip_tags($requested_keyword));
				$keyword = mysql_real_escape_string($requested_keyword);
				
				$d_names_found = 0; // number of search results in the Protein tables.
				$d_synonyms_found = 0; // number of search results in the ProteinNameMap tables.
				$a_protein_ids = array(); // container for protein IDs from both names and synonyms
				
				// PROTEIN IDS FROM NAMES AND SYNONYMS
				// Protein IDs from names
				$sql_prot_ids_from_name = "SELECT DISTINCT id AS proteinId FROM Protein WHERE proteinName LIKE '%$keyword%'";
				$r_prot_ids_from_name = $DB->query($sql_prot_ids_from_name);
				$this->verbose ? $T['verbose_log'] .= "\n $sql_prot_ids_from_name" : '';
				if (!$r_prot_ids_from_name) throw new \ErrorException('Protein name query failed!');
				while($r = $r_prot_ids_from_name->fetch()) { // DBAL fetch is a fuckin memory hog
					$a_protein_ids[$r['proteinId']] = (int)$r['proteinId'];
					$d_names_found++;
				}
				$this->verbose ? $T['verbose_log'] .= "\n $d_names_found protein names found for $sp" : '';
				
				// Protein IDs from synonyms
				// we have to search amongst synonyms too even if we haven't found anything in protein names...
				$sql_prot_ids_from_synonyms = "SELECT DISTINCT proteinId FROM NameToProtein WHERE name LIKE '%$keyword%'";
				$r_prot_ids_from_synonyms = $DB->query($sql_prot_ids_from_synonyms);
				$this->verbose ? $T['verbose_log'] .= "\n $sql_prot_ids_from_synonyms" : '';
				if (!$r_prot_ids_from_synonyms) throw new \ErrorException('Protein synonyms query failed!');
				while($r = $r_prot_ids_from_synonyms->fetch()) {
					$a_protein_ids[$r['proteinId']] = (int)$r['proteinId'];
					$d_synonyms_found++;
				}
				$this->verbose ? $T['verbose_log'] .= "\n $d_synonyms_found synonyms found for $sp" : '';
				
				if (!empty($a_protein_ids)) {
					// PAGINATION
					$sql_pg = "SELECT COUNT(id) AS proteinCount FROM Interaction WHERE (actorAId=".join(' OR actorAId=', $a_protein_ids).") OR (actorBId=".join(' OR actorBId=', $a_protein_ids).")";			
					$r_pg = $DB->query($sql_pg);
					$this->verbose ? $T['verbose_log'] .= "\n Pagination: $sql_pg" : '';
					$a_rownum = $r_pg->fetch();
					$sum_protein_count = (int)$a_rownum['proteinCount'];
					
					// INTERACTIONS OF PREVIOUSLY DETERMINED PROTEIN IDS
					$locs = $this->get('comppi.build.localizationTranslator');
					$sql_i = "
						SELECT DISTINCT
							p1.proteinName AS protA,
							p2.proteinName AS protB,
							i.actorAId,
							i.actorBId,
							ptl1.localizationId AS locAId,
							ptl1.pubmedId AS locASrc,
							ptl2.localizationId AS locBId,
							ptl2.pubmedId AS locBSrc
						FROM Interaction i
						LEFT JOIN Protein p1 ON i.actorAId=p1.id
						LEFT JOIN Protein p2 ON i.actorBId=p2.id
						LEFT JOIN ProteinToLocalization ptl1 ON actorAId=ptl1.proteinId
						LEFT JOIN ProteinToLocalization ptl2 ON actorBId=ptl2.proteinId
						WHERE
							(i.actorAId=".join(' OR i.actorAId=', $a_protein_ids).") OR (i.actorBId=".join(' OR i.actorBId=', $a_protein_ids).")"
							//.(!$this->null_loc_needed ? " AND (ptl1.localizationId IS NOT NULL AND ptl2.localizationId IS NOT NULL)" : '')
							.($this->search_result_per_page ? " LIMIT ".$this->search_range_start.", ".$this->search_result_per_page : '');
					
					$this->verbose ? $T['verbose_log'] .= "\n $sql_i" : '';
					//exit($sql);
					
					$r_i = $DB->query($sql_i);
					if (!$r_i) throw new \ErrorException('Interaction query failed!');
					while ( $p = $r_i->fetch() ) {
						$T['ls'][] = array(
							'protA' => $p['protA'],
							'locA' => (empty($p['locAId']) ? 'N/A' : $locs->getHumanReadableLocalizationById($p['locAId'])),
							'locASrcUrl' => (empty($p['locAId']) ? '' : $this->linkToPubmed($p['locASrc'])),
							'protB' => $p['protB'],
							'locB' => (empty($p['locBId']) ? 'N/A' : $locs->getHumanReadableLocalizationById($p['locBId'])),
							'locBSrcUrl' => (empty($p['locBId']) ? '' : $this->linkToPubmed($p['locBSrc']))
						);
					}
					
					$result_msg_text = '%d protein'.($d_names_found>1 ? 's' : '')
						.' with %d synonym'.($d_synonyms_found>1 ? 's' : '')
						.' and %d interaction'.($sum_protein_count>1 ? 's' : '')
						.' were found.';
					$T['result_msg'] = sprintf($result_msg_text, $d_names_found, $d_synonyms_found, $sum_protein_count);
				} else {
					$T['result_msg'] = 'No matching protein name (or synonym) was found.';
				}
			} else {
				// @TODO: set up a symfony-style proper form validation
				$T['result_msg'] = 'Please fill in a protein name and select a species!';
			}
		}
		
		$T['need'] = $this->mapSpeciesToTemplate();
		
		return $this->render('ComppiProteinSearchBundle:ProteinSearch:index.html.twig', $T);
	}
	
	private function linkToPubmed($pubmed_uid)
	{
		return 'http://www.ncbi.nlm.nih.gov/pubmed/'.$pubmed_uid;
	}
	
	private function mapSpeciesRequest()
	{
		$request = $this->getRequest();
		foreach($this->species_map as $sp => $d) {
			if ($request->request->get($d['form_field'])) {
				$this->species_map[$sp]['needed'] = true;
			} else {
				$this->species_map[$sp]['needed'] = false;
			}
		}
		return;
	}
	
	private function mapSpeciesToTemplate()
	{
		foreach($this->species_map as $sp => $d) {
			if ($this->species_map[$sp]['needed']) {
				$needed[strtolower($sp)] = 1;
			}  else {
				$needed[strtolower($sp)] = 0;
			}
		}
		
		return $needed;
	}
	
	private function getRequestedSpecies()
	{
		foreach($this->species_map as $sp => $d)
			if (!empty($d['needed']))	return $sp;
		
		return null;
	}
}
