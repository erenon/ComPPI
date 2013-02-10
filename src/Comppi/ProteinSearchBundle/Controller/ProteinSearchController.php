<?php

namespace Comppi\ProteinSearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class ProteinSearchController extends Controller
{
	private $speciesProvider = null;
	private $localizationTranslator = null;
	private $verbose = false;
	private $search_range_start = 0; // current page * search_result_per_page -> search query limit from here
	private $search_result_per_page = 10; // search query limit offset (0: no limit)
	
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
			'result_msg' => ''
        );

		$request = $this->getRequest();
		if (!empty($keyword) and !empty($species))
		{
			$DB = $this->get('database_connection');

			$T['keyword']  = htmlspecialchars(strip_tags($keyword));
			$keyword = mysql_real_escape_string($keyword);
			
			$d_names_found = 0; // number of search results in the Protein tables.
			$d_synonyms_found = 0; // number of search results in the ProteinNameMap tables.
			$a_protein_ids = array(); // container for protein IDs from both names and synonyms
			
			// PROTEIN IDS FROM NAMES AND SYNONYMS
			// Protein IDs from names
			$sql_prot_ids_from_name = "SELECT DISTINCT id AS proteinId FROM Protein WHERE (specieId=".join(' OR specieId=', $species).") AND proteinName LIKE '%$keyword%'";
			$r_prot_ids_from_name = $DB->query($sql_prot_ids_from_name);
			$this->verbose ? $T['verbose_log'] .= "\n $sql_prot_ids_from_name" : '';
			if (!$r_prot_ids_from_name)
				throw new \ErrorException('Protein name query failed!');
			while($r = $r_prot_ids_from_name->fetch()) // DBAL fetch is a fuckin memory hog
			{
				$a_protein_ids[$r['proteinId']] = (int)$r['proteinId'];
				$d_names_found++;
			}
			$this->verbose ? $T['verbose_log'] .= "\n $d_names_found protein names found" : '';
			
			// Protein IDs from synonyms
			// we have to search amongst synonyms too even if we haven't found anything in protein names...
			$sql_prot_ids_from_synonyms = "SELECT DISTINCT proteinId FROM NameToProtein WHERE (specieId=".join(' OR specieId=', $species).") AND name LIKE '%$keyword%'";
			$r_prot_ids_from_synonyms = $DB->query($sql_prot_ids_from_synonyms);
			$this->verbose ? $T['verbose_log'] .= "\n $sql_prot_ids_from_synonyms" : '';
			if (!$r_prot_ids_from_synonyms)
				throw new \ErrorException('Protein synonyms query failed!');
			while($r = $r_prot_ids_from_synonyms->fetch())
			{
				$a_protein_ids[$r['proteinId']] = (int)$r['proteinId'];
				$d_synonyms_found++;
			}
			$this->verbose ? $T['verbose_log'] .= "\n $d_synonyms_found synonyms found" : '';

			if (!empty($a_protein_ids))
			{
				$db_cond[] = "((p1.specieId=".join(' OR p1.specieId=', $species)
					.") AND (p2.specieId=".join(' OR p2.specieId=', $species)."))";
				$db_cond[] = "(i.actorAId=".join(' OR i.actorAId=', $a_protein_ids).") OR (i.actorBId=".join(' OR i.actorBId=', $a_protein_ids).")";
				
				// INTERACTIONS OF PREVIOUSLY DETERMINED PROTEIN IDS
				$locs = $this->getLocalizationTranslator();
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
					LEFT JOIN ProteinToLocalization ptl1 ON i.actorAId=ptl1.proteinId
					LEFT JOIN ProteinToLocalization ptl2 ON i.actorBId=ptl2.proteinId
					WHERE "
						.join(' AND ', $db_cond)
						//.(!$this->null_loc_needed ? " AND (ptl1.localizationId IS NOT NULL AND ptl2.localizationId IS NOT NULL)" : '')
						.($this->search_result_per_page ? " LIMIT ".$this->search_range_start.", ".$this->search_result_per_page : '');
				
				$this->verbose ? $T['verbose_log'] .= "\n $sql_i" : '';
				//exit($sql_i);
				
				$r_i = $DB->query($sql_i);
				if (!$r_i) throw new \ErrorException('Interaction query failed!');
				while ($p = $r_i->fetch())
				{
					$T['ls'][] = array(
						'protA' => $p['protA'],
						'locA' => (empty($p['locAId']) ? 'N/A' : $locs->getHumanReadableLocalizationById($p['locAId'])),
						'locASrcUrl' => (empty($p['locAId']) ? '' : $this->linkToPubmed($p['locASrc'])),
						'protB' => $p['protB'],
						'locB' => (empty($p['locBId']) ? 'N/A' : $locs->getHumanReadableLocalizationById($p['locBId'])),
						'locBSrcUrl' => (empty($p['locBId']) ? '' : $this->linkToPubmed($p['locBSrc']))
					);
				}
				
				// CONFIDENCE SCORES
				
				
				// PAGINATION
				$sql_pg = "SELECT COUNT(i.id) AS proteinCount
					FROM Interaction i
					LEFT JOIN Protein p1 ON i.actorAId=p1.id
					LEFT JOIN Protein p2 ON i.actorBId=p2.id WHERE ".join(' AND ', $db_cond);
				$r_pg = $DB->query($sql_pg);
				$this->verbose ? $T['verbose_log'] .= "\n Pagination: $sql_pg" : '';
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
		
		return $this->render('ComppiProteinSearchBundle:ProteinSearch:index.html.twig', $T);
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
	
	private function getLocalizationTranslator()
	{
		if (!$this->localizationTranslator)
			$this->localizationTranslator = $this->get('comppi.build.localizationTranslator');
			
		return $this->localizationTranslator;
	}
}
