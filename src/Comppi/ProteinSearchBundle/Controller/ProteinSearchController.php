<?php

namespace Comppi\ProteinSearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class ProteinSearchController extends Controller
{
    // @TODO: implement a central input processing bundle/service + Symfony-style forms
	private $species_requested = 'Hs';
	private $verbose = false;
	//private $null_loc_needed = false; // show rows where one or both locations are unknown
	private $search_range_start = 0; // search result display starts from here
	private $search_result_per_page = 20; // limit the number of displayed results to ... (0 = unlimited)
	
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
			die( var_dump($request->request->get('fProtSearchSpecDm')) );
			if ($request->request->get('fProtSearchSpecHs')) {
				$this->species_requested = 'Hs';
			} elseif ($request->request->get('fProtSearchSpecDm')) {
				$this->species_requested = 'Dm';
			} elseif ($request->request->get('fProtSearchSpecCe')) {
				$this->species_requested = 'Ce';
			} elseif ($request->request->get('fProtSearchSpecSc')) {
				$this->species_requested = 'Sc';
			} else {
				$this->species_requested = 'Hs';
			}
			
			$keywords = array();
			if ($request->request->get('fProtSearchKeyword')) {
				$keywords[] = mysql_real_escape_string($request->request->get('fProtSearchKeyword'));
				$T['keyword']  = $request->request->get('fProtSearchKeyword');
			}
			if ($request->request->get('fProtSearchMultiNames')) {
				$T['protein_names']  = $request->request->get('fProtSearchMultiNames');
				$keywords = array_merge(explode("\n", $request->request->get('fProtSearchMultiNames')), $keywords);
			}

			if (!empty($keywords)) {
				$name_cond = array();
				$name2prot_cond = array();
				foreach($keywords as $name) {
					$name = str_replace("\r", '', $name); // different carriage returns on different platforms...
					$name_cond[] = "(proteinName LIKE '%".mysql_real_escape_string($name)."%')";
					$name2prot_cond[] = "(name LIKE '%".mysql_real_escape_string($name)."%')";
				}
				$this->verbose ? $T['verbose_log'] .= "\n Keywords: ".join(', ', $keywords) : '';

				// Low-level Doctrine DBAL commands with custom query building to have better control
				// @TODO: convert to Doctrine query builder ( conn->createQueryBuilder() )?
				$d_names_found = 0; // number of search results in the Protein tables.
				$d_synonyms_found = 0; // number of search results in the ProteinNameMap tables.
				$d_interactions_found = 0;
				$a_protein_ids = array(); // container for protein IDs from both names and synonyms

				// PROTEIN IDS FROM NAMES AND SYNONYMS
				foreach($this->species_requested as $sp => $specie_needed) {
					//$one_sp_at_least = true;
					
					if ($specie_needed) {
						$this->verbose ? $T['verbose_log'] .= "\n Query cycle for $sp started" : '';
						
						// Doctrine's $DB->fetchAll returns a memory-heavy 2 dimensional array
						// -> we fill our own array which also serves as a pseudo array_unique()
						
						// Protein IDs from names
						$r_prot_ids_from_name = $DB->query( "SELECT DISTINCT id AS proteinId FROM Protein$sp WHERE ".join(' OR ', $name_cond) );
						$this->verbose ? $T['verbose_log'] .= "\n SELECT DISTINCT id AS proteinId FROM Protein$sp WHERE ".join(' OR ', $name_cond) : '';
						if (!$r_prot_ids_from_name) throw new \ErrorException('Protein name query failed!');
						while($r = $r_prot_ids_from_name->fetch()) {
							$a_protein_ids[$sp][$r['proteinId']] = (int)$r['proteinId'];
							$d_names_found++;
						}
						$this->verbose ? $T['verbose_log'] .= "\n $d_names_found protein names found for $sp" : '';
						
						// Protein IDs from synonyms
						// we have to search amongst synonyms too even if we haven't found anything in protein names...
						$r_prot_ids_from_synonyms = $DB->query( "SELECT DISTINCT proteinId FROM NameToProtein$sp WHERE ".join(' OR ', $name2prot_cond) );
						$this->verbose ? $T['verbose_log'] .= "\n SELECT DISTINCT proteinId FROM NameToProtein$sp WHERE ".join(' OR ', $name2prot_cond) : '';
						if (!$r_prot_ids_from_synonyms) throw new \ErrorException('Protein synonyms query failed!');
						while($r = $r_prot_ids_from_synonyms->fetch()) {
							$a_protein_ids[$sp][$r['proteinId']] = (int)$r['proteinId'];
							$d_synonyms_found++;
						}
						$this->verbose ? $T['verbose_log'] .= "\n $d_synonyms_found synonyms found for $sp" : '';
					}
				}
				
				
				
				// PAGINATION
				// Source data is divided to species-based tables, therefore we have to determine the number of pages and their distribution over the tables
				// we have to cycle over all the requested species in between the protein ID cycle (previous) and the interaction cycle (next) because we have to know the number of found proteins in ALL requested species in advance
				//$pg_protein_count = 0; // the number of all proteins found in all the species (for pagination)
				$pagination_frames = array(
					// example: start from 40, result per page 20
					//'Hs' => array('protein_count' => 25, 'start' => 20, 'offset' => 5),
					//'Dm' => array('protein_count' => 32, 'start' => 9, 'offset' => 6),
					//'Ce' => array(),
					//'Sc' => array()
				);
				/*$sum_protein_count = 0; // cursor amongst the frames
				foreach($this->species_requested as $sp => $specie_needed) {
					if ($specie_needed && !empty($a_protein_ids[$sp])) {
						$sql_pg = "SELECT COUNT(DISTINCT actorAId) AS proteinCount FROM Interaction$sp "
							." WHERE actorAId=".join(' OR actorAId=', $a_protein_ids[$sp]).") OR (actorBId=".join(' OR actorBId=', $a_protein_ids[$sp]);
						$this->verbose ? $T['verbose_log'] .= "\n Pagination: SELECT COUNT(DISTINCT actorAId) AS proteinCount FROM Interaction$sp WHERE (actorAId=".join(' OR actorAId=', $a_protein_ids[$sp]).") OR (actorBId=".join(' OR actorBId=', $a_protein_ids[$sp]).")" : '';			
						$r_pg = $DB->query( $sql_pg );
						$a_rownum = $r_pg->fetch();
						$pagination_frames[$sp]['protein_count'] = (int)$a_rownum['proteinCount'];
						$sum_protein_count += (int)$a_rownum['proteinCount'];

						// request within the range of the current species in cycle
						if ($this->search_range_start <= $sum_protein_count - $a_rownum['proteinCount']
						  && ($this->search_range_start + $this->search_result_per_page) <= $a_rownum['proteinCount']
						) {
							$pagination_frames[$sp]['start'] = $this->search_range_start;
							$pagination_frames[$sp]['offset'] = $this->search_result_per_page;
							break; // we have the range, does not need to test further races
						}
						// start is in the range of this species, end is in the range of next species
						elseif ($this->search_range_start<$a_rownum['proteinCount'] && $this->search_range_start+$this->search_result_per_page > $a_rownum['proteinCount']) {
							
						}
						// further races are not needed -> we turn them off
						elseif ($sum_protein_count < $this->search_range_start) {
							break;
						}
						
						if ($this->search_range_start < $a_rownum['proteinCount']) {
							$pagination_frames[$sp]['start'] = $this->search_range_start;
							$pagination_frames[$sp]['offset'] = (int)$a_rownum['proteinCount'] - $this->search_range_start;
							// kovetkezo faj start-ja 0, offset-je meg a perpage minusz elozo faj offset-je
							// figyelni ra, hogy van-e kovetkezo faj
						}
					}
				}*/
				
				//var_dump($pagination_limits);
				//die();
				
				// INTERACTIONS OF PREVIOUSLY DETERMINED PROTEIN IDS
				$locs = $this->get('comppi.build.localizationTranslator');
				//$one_sp_at_least = false;

				foreach($this->species_requested as $sp => $specie_needed) {
					if ($specie_needed && !empty($a_protein_ids[$sp])) {
						// @TODO: ha a limit kezdete és vége nagyobb, mint a results per page, akkor meghekkelték! -> ezt ellenőrizni, visszaállítani results per page-re
						
						$sql = "SELECT DISTINCT p1.proteinName AS protA, p2.proteinName AS protB, i.actorAId, i.actorBId, ptl1.localizationId AS locAId, ptl1.pubmedId AS locASrc, ptl2.localizationId AS locBId, ptl2.pubmedId AS locBSrc"
							." FROM Interaction$sp i
							  LEFT JOIN Protein$sp p1 ON i.actorAId=p1.id
							  LEFT JOIN Protein$sp p2 ON i.actorBId=p2.id
							  LEFT JOIN ProteinToLocalization$sp ptl1 ON actorAId=ptl1.proteinId
							  LEFT JOIN ProteinToLocalization$sp ptl2 ON actorBId=ptl2.proteinId "
							.' WHERE ('
								."(i.actorAId=".join(' OR i.actorAId=', $a_protein_ids[$sp]).") OR (i.actorBId=".join(' OR i.actorBId=', $a_protein_ids[$sp]).")"
								//.(!$this->null_loc_needed ? " AND (ptl1.localizationId IS NOT NULL AND ptl2.localizationId IS NOT NULL)" : '')
								.' )'
								.($this->search_result_per_page ? ' LIMIT '.$this->search_result_per_page : '');
						
						$this->verbose ? $T['verbose_log'] .= "\n $sql" : '';
						//exit($sql);
						
						$r_interactions = $DB->query( $sql );
						if (!$r_interactions) throw new \ErrorException('Interaction query failed!');
						while ( $p = $r_interactions->fetch() ) {
							$T['ls'][] = array(
								'protA' => $p['protA'],
								'locA' => (empty($p['locAId']) ? 'N/A' : $locs->getHumanReadableLocalizationById($p['locAId'])),
								'locASrcUrl' => (empty($p['locAId']) ? '' : $this->linkToPubmed($p['locASrc'])),
								'protB' => $p['protB'],
								'locB' => (empty($p['locBId']) ? 'N/A' : $locs->getHumanReadableLocalizationById($p['locBId'])),
								'locBSrcUrl' => (empty($p['locBId']) ? '' : $this->linkToPubmed($p['locBSrc'])),
								'species' => $sp
							);
							$d_interactions_found++;
						}
					}
				}
				
				if (empty($T['ls'])) {
					$T['result_msg'] = 'No matching protein name (or synonym) was found.';
				} else {
					$result_msg_text = '%d protein'.($d_names_found>1 ? 's' : '')
						.' with %d synonym'.($d_synonyms_found>1 ? 's' : '')
						.' and %d interaction'.($d_interactions_found>1 ? 's' : '')
						.' were found.';
					$T['result_msg'] = sprintf($result_msg_text, $d_names_found, $d_synonyms_found, $d_interactions_found);
				}
				
				//die( var_dump($T['ls']) );
				/*if ( !$one_sp_at_least ) {
					$this->get('session')->setFlash('notice', 'Please select at least one genus!');
				}*/
			} else {
				// @TODO: set up a symfony-style proper form validation
				$this->get('session')->setFlash('notice', 'Please fill in at least one protein name!');
			}
		}
		
		$T['need_hs'] = $this->species_requested['Hs'];
		$T['need_dm'] = $this->species_requested['Dm'];
		$T['need_ce'] = $this->species_requested['Ce'];
		$T['need_sc'] = $this->species_requested['Sc'];
		
		return $this->render('ComppiProteinSearchBundle:ProteinSearch:index.html.twig', $T);
	}
	
	private function linkToPubmed($pubmed_uid)
	{
		return 'http://www.ncbi.nlm.nih.gov/pubmed/'.$pubmed_uid;
	}
}
