<?php

namespace Comppi\ProteinSearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class ProteinSearchController extends Controller
{
	private $speciesProvider = null;
	private $localizationTranslator = null;
	private $major_loc_gos = array (
		'GO:0043226',
		'GO:0005739',
		'GO:0005634',
		'GO:0005576',
		'GO:secretory_pathway',
		'GO:0016020'
	);
	private $verbose = false;
	private $verbose_log = array();
	private $search_range_start = 0; // current page * search_result_per_page -> search query limit from here
	private $search_result_per_page = 10; // search query limit offset (0: no limit)
	private $uniprot_root = 'http://www.uniprot.org/uniprot/';
	
	/* PROTEIN SEARCH
	 * This function provides a search form and display the results of the protein search.
	 * Protein details are loaded for the whole result set, not one by one (via AJAX) because
	 * 	1) it is faster to open huge MySQL tables only once, not as many times as an interaction detail is displayed,
	 * 	2) it scales better (speed is basically the same for 10 and for 50 results too),
	 *	3) we have to display the large localizations in the result set even when no details are shown...
	 */
	public function proteinSearchAction($protein_name, $requested_species, $current_page)
    {
		$keyword = $this->initKeyword($protein_name);
		$species = $this->initSpecies($requested_species);
		$current_page = $this->initPageNum($current_page);
		$specprov = $this->getSpeciesProvider();
		$descriptors = $specprov->getDescriptors();
		
		$T = array(
			'verbose_log' => '',
			'species_list' => $descriptors,
            'ls' => array(),
			'keyword' => '',
			'result_msg' => '',
			'uniprot_root' => $this->uniprot_root
        );

		$request = $this->getRequest();
		if (!empty($keyword) and !empty($species))
		{
			$DB = $this->getDbConnection();
			$T['keyword']  = htmlspecialchars(strip_tags($keyword));
			$a_protein_ids = array(); // container for protein IDs from both names and synonyms
			
			// Comppi IDs from protein names
			$d_names_found = $this->getProteinIdsFromNames($keyword, $species, $a_protein_ids);
			
			// Comppi IDs from synonyms
			// we have to search amongst synonyms too even if we haven't found anything in protein names...
			$d_synonyms_found = $this->getProteinIdsFromSynonyms($keyword, $species, $a_protein_ids);

			// INTERACTIONS 1 - GET THE INTERACTOR IDS FROM DATABASE
			// we have to get first the interaction rows (and can attach the details later)
			if (!empty($a_protein_ids))
			{
				$db_cond[] = "((p1.specieId=".join(' OR p1.specieId=', $species)
					.") AND (p2.specieId=".join(' OR p2.specieId=', $species)."))";
				$db_cond[] = "(i.actorAId=".join(' OR i.actorAId=', $a_protein_ids).") OR (i.actorBId=".join(' OR i.actorBId=', $a_protein_ids).")";
				
				$sql_i = "
					SELECT DISTINCT
						p1.proteinName AS protA,
						p2.proteinName AS protB,
						i.id AS iid,
						i.actorAId AS p1id,
						i.actorBId AS p2id
					FROM Interaction i
					INNER JOIN Protein p1 ON i.actorAId=p1.id
					INNER JOIN Protein p2 ON i.actorBId=p2.id
					WHERE "
						.join(' AND ', $db_cond)
						.($this->search_result_per_page ? " LIMIT ".$this->search_range_start.", ".$this->search_result_per_page : '');
				
				$this->verbose ? $this->verbose_log[] =  "$sql_i" : '';
				//exit($sql_i);
				
				$r_i = $DB->query($sql_i);
				if (!$r_i) throw new \ErrorException('Interaction query failed!');
				while ($p = $r_i->fetchObject())
				{
					// skeleton of the template
					$T['ls'][$p->iid] = array(
						'iid' => $p->iid,
						'protA' => $p->protA,
						'protB' => $p->protB,
						'p1id' => $p->p1id,
						'p2id' => $p->p2id,
					);
					// we collect the interactor IDs and protein names (and make them unique by adding by index!) to get the localizations and synonyms
					$actor_ids[$p->p1id] = $p->p1id;
					$actor_ids[$p->p2id] = $p->p2id;
					$protein_names[$p->protA] = $p->protA;
					$protein_names[$p->protB] = $p->protB;
				}
				
				// CONFIDENCE SCORES
				//die(var_dump( $T['ls'] ));
				
				// INTERACTIONS 2 - FILL THE INTERACTION SKELETON WITH DETAILS
				if (!empty($actor_ids))
				{
					// localizations: large & small
					$prot_loc_data = $this->getProteinLocalizations($a_protein_ids);
					//die(var_dump($prot_loc_data));
					$synonyms = $this->getProteinSynonyms($protein_names, $species);
					
					foreach ($T['ls'] as $iid => $data) // notice that we don't touch the original data!
					{
						$p1id = $data['p1id'];
						$p2id = $data['p2id'];
						$p1name = $data['protA'];
						$p2name = $data['protB'];
						
						// large loc(s) for protein A
						if (isset($prot_loc_data[$p1id])) {
							foreach($prot_loc_data[$p1id] AS $loc1_id => $ld) {
								$T['ls'][$iid]['locA'][ucfirst(substr($ld['large_loc_name'], 0,  1))] = $ld['large_loc_name'];
								$T['ls'][$iid]['protA_small_locs'][] = $ld['small_loc_name'];
								$T['ls'][$iid]['protA_source_dbs'][] = $ld['source_db'];
								$T['ls'][$iid]['protA_pubmed_links'][] = $this->linkToPubmed($ld['pubmed_id']);
								$T['ls'][$iid]['protA_loc_exp_types'][] = $ld['loc_exp_sys_type'];
							}
						} else {
							$T['ls'][$iid]['locA']['-'] = 'N/A';
						}
	
						// large loc(s) for protein B
						if (isset($prot_loc_data[$p2id])) {
							foreach($prot_loc_data[$p2id] AS $loc2_id => $ld) {
								$T['ls'][$iid]['locB'][ucfirst(substr($ld['large_loc_name'], 0,  1))] = $ld['large_loc_name'];
								$T['ls'][$iid]['protB_small_locs'][] = $ld['small_loc_name'];
								$T['ls'][$iid]['protB_source_dbs'][] = $ld['source_db'];
								$T['ls'][$iid]['protB_pubmed_links'][] = $this->linkToPubmed($ld['pubmed_id']);
								$T['ls'][$iid]['protB_loc_exp_types'][] = $ld['loc_exp_sys_type'];
							}
						} else {
							$T['ls'][$iid]['locB']['-'] = 'N/A';
						}
						
						// synonyms
						if (isset($synonyms[$p1name]))
							$T['ls'][$iid]['protA_synonyms'] = join(', ', $synonyms[$p1name]);
						if (isset($synonyms[$p2name]))
							$T['ls'][$iid]['protB_synonyms'] = join(', ', $synonyms[$p2name]);
					
					}
				}
				else
				{
					$T['result_msg'] = 'No interactions were found for these proteins.';
				}
				
				// PAGINATION
				$sql_pg = "SELECT COUNT(i.id) AS proteinCount
					FROM Interaction i
					LEFT JOIN Protein p1 ON i.actorAId=p1.id
					LEFT JOIN Protein p2 ON i.actorBId=p2.id WHERE ".join(' AND ', $db_cond);
				$r_pg = $DB->query($sql_pg);
				$this->verbose ? $this->verbose_log[] =  "Pagination: $sql_pg" : '';
				$a_rownum = $r_pg->fetch();
				$sum_interaction_count = (int)$a_rownum['proteinCount'];
				
				$T['pagination_ls'] = array();
				$T['pagination_base_url'] = './protein_search/'
					.urlencode($keyword).'/'
					.join(',', array_keys($species)).'/';
				$T['pagination_curr_page'] = $current_page;
				$T['pagination_max_page'] = floor($sum_interaction_count/$this->search_result_per_page);
				
				// RESULT LINE
				$result_msg_text = '%d protein'.($d_names_found>1 ? 's' : '')
					.' with %d synonym'.($d_synonyms_found>1 ? 's' : '')
					.' and %d interaction'.($sum_interaction_count>1 ? 's' : '')
					.' were found.';
				$T['result_msg'] = sprintf($result_msg_text, $d_names_found, $d_synonyms_found, $sum_interaction_count);
			}
			else
			{
				$T['result_msg'] = 'No matching protein name (or synonym) was found.';
			}
		}
		
		$T['requested_species'] = $species;
		$T['verbose_log'] = str_replace("\t", "", join("\n-----\n", $this->verbose_log));
		
		return $this->render('ComppiProteinSearchBundle:ProteinSearch:index.html.twig', $T);
	}

	
	// @var array The list of comppi ids
	private function getProteinLocalizations($protein_ids)
	{
		// Get the branch left and right borders of major locs - between the primary and secondary id of a major loc are the loc ids of that branch
		$locs = $this->getLocalizationTranslator();
		$major_locs = array();
		foreach($this->major_loc_gos as $i => $go)
		{
			$major_locs[$i] = array(
				'id1' => $locs->getIdByLocalization($go),
				'id2' => $locs->getSecondaryIdByLocalization($go),
				'name' => $locs->getHumanReadableLocalizationById($locs->getIdByLocalization($go))
			);
		}
		
		$sql_pl = 'SELECT DISTINCT
				ptl.proteinId, ptl.localizationId AS locId, ptl.sourceDb, ptl.pubmedId, st.name AS exp_sys_type
			FROM ProteinToLocalization ptl, ProtLocToSystemType pltst, SystemType st
			WHERE ptl.id=pltst.protLocId
				AND pltst.systemTypeId=st.id
				AND (proteinId='.join(' OR proteinId=', $protein_ids).');';
		$this->verbose ? $this->verbose_log[] = $sql_pl : '';
		
		if (!$pl = $this->DB->executeQuery($sql_pl))
			throw new \ErrorException('ProteinToLocalization query failed!');

		$protein_locs = array();
		while ($p = $pl->fetchObject())
		{
			// build the human readable localization tree for the requested proteins
			foreach($major_locs as $l)
			{
				//echo $l["name"].': '.$l["id1"] .' / '/*.$p->locId.' < '*/. $l["id2"] . "\n";
				if ($l["id1"]<=$p->locId and $l["id2"]>$p->locId) {
					$protein_locs[$p->proteinId][$p->locId] = array(
						'small_loc_name' => ucfirst($locs->getHumanReadableLocalizationById($p->locId)),
						'large_loc_name' => $l['name'],
						'source_db' => $p->sourceDb,
						'pubmed_id' => $p->pubmedId,
						'loc_exp_sys_type' => $p->exp_sys_type,
					);
				}
			}
		}
		$this->verbose ? $this->verbose_log[] = count($protein_locs).' protein locations found' : '';

        return $protein_locs;
	}
	
	
	// GET THE SYNONYMS OF PROTEINS BY THEIR COMPPI IDS
	// @var array the list of names of proteins
	// @var array list of species
	private function getProteinSynonyms($protein_names, $species)
	{
		foreach($protein_names AS $name)
			$cond[] = "(proteinNameA='".mysql_real_escape_string($name)
				 ."' OR proteinNameB='".mysql_real_escape_string($name)."')";
		
		$sql_syn = "SELECT proteinNameA, namingConventionA, proteinNameB, namingConventionB
			FROM ProteinNameMap
			WHERE (".join(" OR ", $cond).")"
			  ." AND (specieId=".join(' OR specieId=', $species).")";
		$this->verbose ? $this->verbose_log[] = $sql_syn : '';

		if (!$syn = $this->DB->executeQuery($sql_syn))
			throw new \ErrorException('ProteinNameMap query (in getProteinSynonyms) failed!');
		
		$protein_synonyms = array();
		while ($s = $syn->fetchObject())
		{
			if (in_array($s->proteinNameA, $protein_names) ) {
				$protein_synonyms[$s->proteinNameA][] = $s->proteinNameB.' ('.$s->namingConventionB.')';
			} else {
				$protein_synonyms[$s->proteinNameB][] = $s->proteinNameA.' ('.$s->namingConventionA.')';
			}
		}
		return $protein_synonyms;
	}

	
	// PROTEIN IDS FROM NAMES
	// @var string keyword (protein name)
	// @var array the list of species IDs
	// @var array reference to the protein ID container (more efficient than passing by copy)
	private function getProteinIdsFromNames($keyword, $species, &$protein_ids)
	{
		$names_found = 0;
		
		$sql_prot_ids_from_name = "SELECT DISTINCT id AS proteinId
			FROM Protein
			WHERE (specieId=".join(' OR specieId=', $species).")
			  AND proteinName LIKE '%".mysql_real_escape_string($keyword)."%'";
		$this->verbose ? $this->verbose_log[] = "$sql_prot_ids_from_name" : '';
		
		if (!$r_prot_ids_from_name = $this->DB->executeQuery($sql_prot_ids_from_name))
			throw new \ErrorException('Protein name query failed!');
		
		while($r = $r_prot_ids_from_name->fetchObject()) // DBAL fetch is a fuckin memory hog
		{
			$protein_ids[$r->proteinId] = (int)$r->proteinId;
			$names_found++;
		}
		$this->verbose ? $this->verbose_log[] = "$names_found protein names found" : '';
		
		return $names_found;
	}

	
	// PROTEIN IDS FROM SYNONYMS
	// @var string keyword (protein name)
	// @var array the list of species IDs
	// @var array reference to the protein ID container (more efficient than passing by copy)
	private function getProteinIdsFromSynonyms($keyword, $species, &$protein_ids)
	{
		$synonyms_found = 0;
		
		$sql_prot_ids_from_synonyms = "SELECT DISTINCT proteinId
			FROM NameToProtein
			WHERE (specieId=".join(' OR specieId=', $species).")
			  AND name LIKE '%".mysql_real_escape_string($keyword)."%'";
		$this->verbose ? $this->verbose_log[] = "$sql_prot_ids_from_synonyms" : '';
		
		if (!$r_prot_ids_from_synonyms = $this->DB->executeQuery($sql_prot_ids_from_synonyms))
			throw new \ErrorException('Protein synonyms query failed!');
		
		while($r = $r_prot_ids_from_synonyms->fetchObject())
		{
			$protein_ids[$r->proteinId] = (int)$r->proteinId;
			$synonyms_found++;
		}
		$this->verbose ? $this->verbose_log[] = "$synonyms_found synonyms found" : '';
		
		return $synonyms_found;
	}
	
	
	private function linkToPubmed($pubmed_uid)
	{
		return 'http://www.ncbi.nlm.nih.gov/pubmed/'.$pubmed_uid;
	}
	
	private function initKeyword($protein_name)
	{
		
		// $request->request->get('fProtSearchKeyword') is not empty even if no keyword was filled in!
		if (!empty($_POST['fProtSearchKeyword']))
		{
			$keyword = $_POST['fProtSearchKeyword'];
		}
		else if (!empty($protein_name))
		{
			$keyword = $protein_name;
		}
		// Form was submitted, but we haven't had any keyword
		elseif (isset($_POST['fProtSearchSubmit']))
		{
			$this->get('session')->getFlashBag()->add('no_keyword_err', 'Please fill in a keyword!');
			$keyword = '';
		}
		else
		{
			$keyword = '';
		}
		return $keyword;
	}
	
	/*
		@var $requested_species the list of species abbreviations separated by commas, e.g. hs,ce
	*/
	private function initSpecies($requested_species = '')
	{
		$species = array();
		$species_provider = $this->getSpeciesProvider();
		
		if (!empty($_POST['fProtSearchSpecies']) and is_array($_POST['fProtSearchSpecies'])) {
			$sp = $_POST['fProtSearchSpecies'];
		} else if (!empty($requested_species)) {
			$sp = array_fill_keys(explode(',', $requested_species), 1);
		} else {
			$sp = array('hs'=>1);
		}
		
		// array of species requested in the form
		// currently one species per request, but this block ensures it can be extended easily
		foreach($sp as $sp_key => $needed)
		{
			// this ensures that we need an exact match from the input to be valid
			// if we don't get back an object, then the form was forged
			$descriptor = @$species_provider->getSpecieByAbbreviation($sp_key); 
			if (is_object($descriptor)) {
				$species[$sp_key] = $descriptor->id;
			}
		}
		
		// add the taxonomical abbreviations of all species, they'll be needed on the species selector buttons
		$descriptors = $species_provider->getDescriptors();
		foreach($descriptors as $o)
		{
			$o->shortname = substr_replace($o->name, '. ', 1, strpos($o->name, ' '));
		}
		
		if (empty($species)) {
			$this->get('session')->getFlashBag()->add('no_species_err', 'Please select at least one species!');
			return false;
		} else {
			return $species;
		}
	}
	
	private function initPageNum($curr_page)
	{
		$page = (preg_match('/^[0-9][0-9]*$/', $curr_page) ? (int)$curr_page : 0);
		$this->search_range_start = $page * $this->search_result_per_page;
		
		return $page;
	}
	
	private function getSpeciesProvider()
	{
		if (!$this->speciesProvider)
			$this->speciesProvider = $this->get('comppi.build.specieProvider');
			
		return $this->speciesProvider;
	}
	
	private function getDbConnection()
	{
		if (empty($this->DB))
				$this->DB = $this->get('database_connection');
		return $this->DB;
	}
	
	private function getLocalizationTranslator()
	{
		if (!$this->localizationTranslator)
			$this->localizationTranslator = $this->get('comppi.build.localizationTranslator');
			
		return $this->localizationTranslator;
	}
}
