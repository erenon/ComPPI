<?php

namespace Comppi\ProteinSearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class ProteinSearchController extends Controller
{
    // @TODO: implement a central input processing bundle/service + Symfony-style forms
	private $species_requested = array(
		'Hs' => 1,
		'Dm' => 1,
		'Ce' => 1,
		'Sc' => 1
	);
	// internal switches to create demonstration and presentation figures
	private $null_loc_needed = false; // show rows where one or both locations are unknown
	private $result_set_limit = 0; // limit the number of displayed results to ... (0 = unlimited)
	
	public function proteinSearchAction($protein_name = '')
    {
        $T = array(
            'ls' => array(),
			'keyword' => '',
			'protein_names' => '',
			'result_msg' => ''
        );

		$request = $this->getRequest();
		if ($request->getMethod() == 'POST') {
			$DB = $this->get('database_connection');
			
			$this->species_requested['Hs'] = intval($request->request->get('fProtSearchSpecHs'));
			$this->species_requested['Dm'] = intval($request->request->get('fProtSearchSpecDm'));
			$this->species_requested['Ce'] = intval($request->request->get('fProtSearchSpecCe'));
			$this->species_requested['Sc'] = intval($request->request->get('fProtSearchSpecSc'));
			
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
					//$pg_cond[] = "(p.proteinName LIKE '%".mysql_real_escape_string($name)."%')"; pager condition
				}

				// Low-level Doctrine DBAL commands with custom query building to have better control
				// @TODO: convert to Doctrine query builder ( conn->createQueryBuilder() )?
				$locs = $this->get('comppi.build.localizationTranslator');
				$one_sp_at_least = false;
				$a_protein_ids = array(); // container for protein IDs from both names and synonyms
				$d_names_found = 0; // number of search results in the Protein tables.
				$d_synonyms_found = 0; // number of search results in the ProteinNameMap tables.
				$d_interactions_found = 0;

				foreach($this->species_requested as $sp => $specie_needed) {
					$one_sp_at_least = true;
					if ( $specie_needed ) {
						// Doctrine's $DB->fetchAll returns a 2 dimensional array, which consumes memory like hell
						// -> we fill a 1 dimensional array, which also serves as a pseudo array_unique()
						// IDS FROM PROTEIN NAMES
						$r_prot_ids_from_name = $DB->query( "SELECT DISTINCT id AS proteinId FROM Protein$sp WHERE ".join(' OR ', $name_cond) );
						if ( !$r_prot_ids_from_name ) throw new Exception('Protein name query failed!');
						while($r = $r_prot_ids_from_name->fetch()) {
							$a_protein_ids[$r['proteinId']] = (int)$r['proteinId'];
						}
						
						if ( !empty($a_protein_ids) ) {
							$d_names_found += count($a_protein_ids);
							// IDS  FROM PROTEIN SYNONYMS
							$r_prot_ids_from_synonyms = $DB->query( "SELECT DISTINCT proteinId FROM NameToProtein$sp WHERE ".join(' OR ', $name2prot_cond) );
							if ( !$r_prot_ids_from_synonyms ) throw new Exception('Protein synonyms query failed!');
							while($r = $r_prot_ids_from_synonyms->fetch()) {
								$a_protein_ids[$r['proteinId']] = (int)$r['proteinId'];
								$d_synonyms_found++;
							}
							
							$s_protein_ids_cond = join(',', $a_protein_ids);
							
							/*$sql_pg = "SELECT DISTINCT COUNT(i.actorAId) AS rownum FROM Interaction$sp i LEFT JOIN Protein$sp p ON i.actorAId=p.id WHERE ".join(' OR ', $pg_cond);
							$r_pg = $DB->query( $sql_pg );
							$r_rownum = $r_pg->fetch();
							$max_rownum = (int)$r_rownum['rownum'];
							// @TODO: to be continued...*/
							
							//var_dump($a_prot_ids_from_name);
							//var_dump($a_prot_ids_from_synonyms);
							//die();
							
							// SELECT ALL LINES FROM INTERACTIONS WHERE ANY OF THE INTERACTORS MATCHES A REQUESTED PROTEIN ID
							$sql = "SELECT DISTINCT p1.proteinName AS protA, p2.proteinName AS protB, i.actorAId, i.actorBId, ptl1.localizationId AS locAId, ptl1.pubmedId AS locASrc, ptl2.localizationId AS locBId, ptl1.pubmedId AS locBSrc "
								."FROM Interaction$sp i
								  LEFT JOIN Protein$sp p1 ON i.actorAId=p1.id
								  LEFT JOIN Protein$sp p2 ON i.actorBId=p2.id
								  LEFT JOIN ProteinToLocalization$sp ptl1 ON actorAId=ptl1.proteinId
								  LEFT JOIN ProteinToLocalization$sp ptl2 ON actorBId=ptl2.proteinId "
								."WHERE
									(p1.id IN($s_protein_ids_cond)
								 OR p2.id IN($s_protein_ids_cond))"
								.(!$this->null_loc_needed ? " AND ptl1.localizationId IS NOT NULL AND ptl2.localizationId IS NOT NULL" : '')
								.($this->result_set_limit ? ' LIMIT '.$this->result_set_limit : '');
	
							//exit($sql);
							
							$r_interactions = $DB->query( $sql );
							if ( !$r_interactions ) throw new Exception('Interaction query failed!');
							while ( $p = $r_interactions->fetch() ) {
								$T['ls'][] = array(
									'protA' => $p['protA'],
									'locA' => (empty($p['locAId']) ? 'N/A' : $locs->getHumanReadableLocalizationById($p['locAId'])),
									'locASrcUrl' => (empty($p['locAId']) ? '' : $this->linkToPubmed($p['locASrc'])),
									'protB' => $p['protB'],
									'locB' => (empty($p['locBId']) ? 'N/A' : $locs->getHumanReadableLocalizationById($p['locBId'])),
									'locBSrcUrl' => (empty($p['locBId']) ? '' : $this->linkToPubmed($p['locBSrc']))
								);
								$d_interactions_found++;
							}
							$T['result_msg'] = sprintf('%d proteins with %d synonyms and %d interactions were found.', $d_names_found, $d_synonyms_found, $d_interactions_found);
						} else {
							$T['result_msg'] = 'No matching protein name (or synonym) was found.';
						}
						
						
					}
				}
				//die( var_dump($T['ls']) );
				if ( !$one_sp_at_least ) {
					$this->get('session')->setFlash('notice', 'Please select at least one genus!');
				}
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
