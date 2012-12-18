<?php

namespace Comppi\ProteinSearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class ProteinSearchController extends Controller
{
	// @TODO: implement a central input processing bundle/service + Symfony-style forms
	/*private $species_map = array(
		'Hs' => array('form_field' => 'fProtSearchHs', 'needed' => false),
		'Dm' => array('form_field' => 'fProtSearchDm', 'needed' => false),
		'Ce' => array('form_field' => 'fProtSearchCe', 'needed' => false),
		'Sc' => array('form_field' => 'fProtSearchSc', 'needed' => false)
	);*/
	private $species = array(
		'provider' => null,
		'requested_species' => array(
			//species abbreviation => species ID, like 'hs'=>0,'dm'=>1,'ce'=>2,'sc'=>3
		)
	);
	private $localizationTranslator = null;
	private $verbose = false;
	//private $null_loc_needed = false; // show rows where one or both locations are unknown
	private $search_range_start = 0; // search result display starts from here
	private $search_result_per_page = 50; // limit the number of displayed results to ... (0 = unlimited)
	
	public function proteinSearchAction($protein_name = '')
    {
        $species = $this->getSpeciesProvider();
		$descriptors = $species->getDescriptors();
		
		$T = array(
			'verbose_log' => '',
			'species_list' => $descriptors,
            'ls' => array(),
			'keyword' => '',
			'protein_names' => '',
			'result_msg' => ''
        );

		$request = $this->getRequest();
		if ($request->getMethod() == 'POST')
		{
			$DB = $this->get('database_connection');

			$this->validateSpeciesRequest();
			
			$requested_keyword = $_POST['fProtSearchKeyword']; // $request->request->get('fProtSearchKeyword') is not empty even if no keyword was filled in!

			if (!empty($requested_keyword))
			{
				$T['keyword']  = htmlspecialchars(strip_tags($requested_keyword));
				$keyword = mysql_real_escape_string($requested_keyword);
				
				$d_names_found = 0; // number of search results in the Protein tables.
				$d_synonyms_found = 0; // number of search results in the ProteinNameMap tables.
				$a_protein_ids = array(); // container for protein IDs from both names and synonyms
				
				// PROTEIN IDS FROM NAMES AND SYNONYMS
				// Protein IDs from names
				$sql_prot_ids_from_name = "SELECT DISTINCT id AS proteinId FROM Protein WHERE (specieId=".join(' OR specieId=', $this->species['requested_species']).") AND proteinName LIKE '%$keyword%'";
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
				$sql_prot_ids_from_synonyms = "SELECT DISTINCT proteinId FROM NameToProtein WHERE (specieId=".join(' OR specieId=', $this->species['requested_species']).") AND name LIKE '%$keyword%'";
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
					$db_cond[] = "(p1.specieId=".join(' OR p1.specieId=', $this->species['requested_species'])
						." OR p2.specieId=".join(' OR p2.specieId=', $this->species['requested_species']).")";
					$db_cond[] = "(i.actorAId=".join(' OR i.actorAId=', $a_protein_ids).") OR (i.actorBId=".join(' OR i.actorBId=', $a_protein_ids).")";
					
					// PAGINATION
					$sql_pg = "SELECT COUNT(id) AS proteinCount FROM Interaction WHERE (actorAId=".join(' OR actorAId=', $a_protein_ids).") OR (actorBId=".join(' OR actorBId=', $a_protein_ids).")";			
					$r_pg = $DB->query($sql_pg);
					$this->verbose ? $T['verbose_log'] .= "\n Pagination: $sql_pg" : '';
					$a_rownum = $r_pg->fetch();
					$sum_protein_count = (int)$a_rownum['proteinCount'];
					
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
					//exit($sql);
					
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
					
					$result_msg_text = '%d protein'.($d_names_found>1 ? 's' : '')
						.' with %d synonym'.($d_synonyms_found>1 ? 's' : '')
						.' and %d interaction'.($sum_protein_count>1 ? 's' : '')
						.' were found.';
					$T['result_msg'] = sprintf($result_msg_text, $d_names_found, $d_synonyms_found, $sum_protein_count);
				}
				else
				{
					$T['result_msg'] = 'No matching protein name (or synonym) was found.';
				}
			}
			else
			{
				$T['result_msg'] = 'Please fill in a protein name as keyword, select a species and submit the the search again!';
			}
		}
		
		$T['requested_species'] = $this->mapSpeciesToTemplate();
		
		return $this->render('ComppiProteinSearchBundle:ProteinSearch:index.html.twig', $T);
	}
	
	private function linkToPubmed($pubmed_uid)
	{
		return 'http://www.ncbi.nlm.nih.gov/pubmed/'.$pubmed_uid;
	}
	
	// Validates the user input and maps it to $this->requested_species property. Initiates specieProvider if needed.
	private function validateSpeciesRequest()
	{
		$request = $this->getRequest();
		$species_provider = $this->getSpeciesProvider();
		
		if (!empty($_POST['fProtSearchSpecies']) and is_array($_POST['fProtSearchSpecies']))
		{
			foreach($_POST['fProtSearchSpecies'] as $sp => $needed)
			{
				// this ensures that we need an exact match from the input to be valid
				// if we don't get back an object, then the form was forged
				$descriptor = @$species_provider->getSpecieByAbbreviation($sp); 
				if (is_object($descriptor))
				{
					$this->species['requested_species'][$sp] = $descriptor->id;
				}
			}
		}
		else
		{
			throw new \InvalidArgumentException('The requested species was/were invalid!');
		}

		return;
	}
	
	private function mapSpeciesToTemplate()
	{
		return $this->species['requested_species'];
	}
	
	private function getSpeciesProvider()
	{
		if (!$this->species['provider'])
			$this->species['provider'] = $this->get('comppi.build.specieProvider');
			
		return $this->species['provider'];
	}
	
	private function getLocalizationTranslator()
	{
		if (!$this->localizationTranslator)
			$this->localizationTranslator = $this->get('comppi.build.localizationTranslator');
			
		return $this->localizationTranslator;
	}
}
