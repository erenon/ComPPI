<?php

namespace Comppi\ProteinSearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class ProteinSearchController extends Controller
{
	// private $DB; use $this->getDBConnection() @TODO: switch to the conn representation of the controller
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
	private $exptype = array(
		0 => 'Unknown',
		1 => 'Experimental',
		2 => 'Predicted'
	);
	
	// PROTEIN SEARCH
	//public function proteinSearchAction($protein_name, $requested_species, $current_page)
	public function proteinSearchAction()
    {
		$protein_name = '';
		$requested_species = '';
		$current_page = '';
		$keyword = $this->initKeyword($protein_name);
		$species = $this->initSpecies($requested_species);
		$current_page = $this->initPageNum($current_page);
		$sp = $this->getSpeciesProvider();
		$spDescriptors = $sp->getDescriptors();
		
		
		$T = array(
			'verbose_log' => '',
			'species_list' => $spDescriptors,
			'requested_species' => array('hs'=>1),
            'ls' => array(),
			'keyword' => '',
			'result_msg' => '',
			'uniprot_root' => $this->uniprot_root
        );

		//$request = $this->getRequest();
		if (!empty($keyword)) // @TODO: require species
		{
			$DB = $this->getDbConnection();
			// @TODO: save keyword to session $T['keyword']  = htmlspecialchars(strip_tags($keyword));
			
			$r_prots_by_name = $DB->query("SELECT
				n2p.name, n2p.specieId, n2p.proteinId, p.proteinName
			  FROM
				NameToProtein n2p, Protein p
			  WHERE
					n2p.proteinId=p.id
				AND n2p.name='".mysql_real_escape_string($keyword)."'"
			);
			if (!$r_prots_by_name)
				throw new \ErrorException('Interaction query failed!');
			
			// exact match to a protein -> we show its interactions
			if ($r_prots_by_name->rowCount()==1)
			{
				$prot_details = $r_prots_by_name->fetchObject();
				//die(var_dump($prot_details->proteinId));
				// forward creates an internal call to a controller and returns a Response
				return $this->redirect($this->generateUrl(
					'ComppiProteinSearchBundle_interactors',
					array('comppi_id' => $prot_details->proteinId))
                );
			}
			// multiple proteins found -> user has to select
			elseif ($r_prots_by_name->rowCount()>1) {
				while ($p = $r_prots_by_name->fetchObject())
				{
					$T['ls'][] = array(
						'comppi_id' => $p->proteinId,
						'name' => $p->name,
						'name2' => $p->proteinName,
						'species' => $spDescriptors[$p->specieId]->shortname,
						'uniprot_link' => $this->uniprot_root.$p->proteinName
					);
				}
				return $this->render('ComppiProteinSearchBundle:ProteinSearch:middlepage.html.twig', $T);
			}
			// no protein was found
			else
			{
				throw new \NotFoundException('Requested protein was not found!');
			}
		} else {
			return $this->render('ComppiProteinSearchBundle:ProteinSearch:index.html.twig', $T);
		}
	}
	
	
	public function interactorsAction($comppi_id)
	{
		$DB = $this->getDbConnection();
		$locs = $this->getLocalizationTranslator();
		$sp = $this->getSpeciesProvider();
		$spDescriptors = $sp->getDescriptors();
		$T = array();

		// details of requested protein
		$T['protein'] = $this->getProteinDetails($comppi_id);
		
		// @TODO: interakciók száma,
		// @TODO: letölthető dataset
		
		// interactors
		$sql_interactors = 
		"SELECT DISTINCT
			i.id AS iid, i.sourceDb, i.pubmedId,
			p.id as pid, p.proteinName as name, p.proteinNamingConvention as namingConvention
		FROM Interaction i
		LEFT JOIN Protein p ON p.id=IF(actorAId = $comppi_id, i.actorBId, i.actorAId)
		WHERE actorAId = $comppi_id OR actorBId = $comppi_id
		LIMIT ".$this->search_result_per_page;
		
		if (!$r_interactors=$DB->query($sql_interactors))
			throw new \ErrorException('Interactor query failed!');

		while ($i = $r_interactors->fetchObject())
		{
			$T['ls'][$i->pid]['prot_name'] = $i->name;
			$T['ls'][$i->pid]['prot_naming'] = $i->namingConvention;
			//if ($i->namingConvention=='UniProtKB-AC')
				$T['ls'][$i->pid]['uniprot_outlink'] = $this->uniprot_root.$i->name;
			
			$protein_ids[$i->pid] = $i->pid;
		}
		
		if (empty($protein_ids)) throw new \ErrorException('No proteins found by that protein ID!');
		
		// localizations for the protein and its interactors
		$protein_locs = $this->getProteinLocalizations($protein_ids);
		
		// synonyms for the protein and its interactors
		$protein_synonyms = $this->getProteinSynonyms($protein_ids);
		
		foreach($T['ls'] as $pid => &$actor)
		{
			// localizations to interactors
			if (!empty($protein_locs[$pid]))
				$actor['locs'] = $protein_locs[$pid];
			// synonyms to interactors
			if (!empty($protein_synonyms[$pid]['syn_fullname']))
				$actor['syn_fullname'] =  $protein_synonyms[$pid]['syn_fullname'];
			if (!empty($protein_synonyms[$pid]['synonyms']))
				$actor['synonyms'] = $protein_synonyms[$pid]['synonyms'];
			//$actor['syn_namings'] = (empty($protein_synonyms[$pid]['syn_namings']) ? array() : $protein_synonyms[$pid]['syn_namings']);
		}
		//die( var_dump( $T ) );
		
		return $this->render('ComppiProteinSearchBundle:ProteinSearch:interactors.html.twig',$T);
	}
	
	
	public function autocompleteAction($keyword)
	{
		$DB = $this->getDbConnection();
		$r_i = $DB->query("SELECT name FROM ProteinName WHERE name LIKE '%".mysql_real_escape_string($keyword)."%' ORDER BY LENGTH(name) LIMIT 15");
		if (!$r_i) throw new \ErrorException('Autocomplete query failed!');
		
		$list = array();
		while ($p = $r_i->fetchObject())
			$list[] = $p->name;

        return new Response(json_encode($list));
	}
	
	
	private function getProteinDetails($comppi_id)
	{
		$DB = $this->getDbConnection();
		$r_p = $DB->query("SELECT proteinName AS name, proteinNamingConvention AS naming, specieId FROM Protein WHERE id=".mysql_real_escape_string($comppi_id));
		if (!$r_p) throw new \ErrorException('Protein query failed!');
		
		$prot_details = $r_p->fetch(\PDO::FETCH_ASSOC);
		$prot_details['species'] = $prot_details['specieId']; // @TODO: map name to id
		$prot_details['locs'] = $this->getProteinLocalizations(array($comppi_id));
		$prot_details['locs'] = (!empty($prot_details['locs'][$comppi_id]) ? $prot_details['locs'][$comppi_id] : array());
		
		$syns = $this->getProteinSynonyms(array($comppi_id));
		$prot_details['synonyms'] = $syns[$comppi_id]['synonyms'];
		$prot_details['fullname'] = $syns[$comppi_id]['syn_fullname'];
		
		return $prot_details;
	}

	
	// @var array The list of comppi ids
	private function getProteinLocalizations($comppi_ids)
	{
		$DB = $this->getDbConnection();
		$locs = $this->getLocalizationTranslator();
		
		$sql_pl = 'SELECT
				ptl.proteinId as pid, ptl.localizationId AS locId, ptl.sourceDb, ptl.pubmedId,
				st.name AS exp_sys, st.confidenceType AS exp_sys_type
			FROM ProteinToLocalization ptl, ProtLocToSystemType pltst, SystemType st
			WHERE ptl.id=pltst.protLocId
				AND pltst.systemTypeId=st.id
				AND proteinId IN ('.join(',', $comppi_ids).')';
		$this->verbose ? $this->verbose_log[] = $sql_pl : '';
		
		if (!$r_pl = $DB->executeQuery($sql_pl))
			throw new \ErrorException('ProteinToLocalization query failed!');
		
		$i = 0;
		while ($p = $r_pl->fetchObject())
		{
			$i++;
			
			$pl[$p->pid][$i]['source_db'] = $p->sourceDb;
			$pl[$p->pid][$i]['pubmed_link'] = $this->linkToPubmed($p->pubmedId);
			$pl[$p->pid][$i]['loc_exp_sys'] = $this->exptype[$p->exp_sys_type].': '.$p->exp_sys;
			$pl[$p->pid][$i]['loc_exp_sys_type'] = $p->exp_sys_type;
			try {
				$pl[$p->pid][$i]['small_loc'] = ucfirst($locs->getHumanReadableLocalizationById($p->locId));
			} catch (\InvalidArgumentException $e) {
				$pl[$p->pid][$i]['small_loc'] = 'N/A';
			}
			try {
				$pl[$p->pid][$i]['large_loc'] = ucfirst($locs->getLargelocById($p->locId));
			} catch (\InvalidArgumentException $e) {
				$pl[$p->pid][$i]['large_loc'] = 'N/A';
			}
		}
		$this->verbose ? $this->verbose_log[] = count($pl).' protein locations found' : '';

		// if a single id was requested, we return data directly for that
        //return (count($comppi_ids)==1 ? $pl[$comppi_ids[0]] : $pl);
		return (!empty($pl) ? $pl : array());
	}
	
	
	private function getProteinSynonyms($comppi_ids)
	{
		$DB = $this->getDbConnection();
		$sql_syn = "SELECT proteinId AS pid, name, namingConvention FROM NameToProtein WHERE proteinId IN(".join(',', $comppi_ids).")";
		$this->verbose ? $this->verbose_log[] = $sql_syn : '';

		if (!$r_syn = $DB->query($sql_syn))
			throw new \ErrorException('getProteinSynonyms query failed!');
		
		$syns = array();
		while ($s = $r_syn->fetch(\PDO::FETCH_OBJ))
		{
			if ($s->namingConvention=='UniProtFull') {
				$syns[$s->pid]['syn_fullname'] = $s->name; // we have to highlight the full name...
			} else {
				$syns[$s->pid]['synonyms'][] = $s->name.'&nbsp;('.$s->namingConvention.')';
			}
		}
		
		// if a single id was requested, we return data directly for that
        //return (count($comppi_ids)==1 ? $syns[$comppi_ids[0]] : $syns);
		return $syns;
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
