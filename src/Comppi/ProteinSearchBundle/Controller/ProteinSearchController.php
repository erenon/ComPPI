<?php
namespace Comppi\ProteinSearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Comppi\ProteinSearchBundle\Entity\ProteinSearch;

class ProteinSearchController extends Controller
{
	private $speciesProvider = null;
	private $localizationTranslator = null;
	private $species_list = array(
		0 => 'H. sapiens',
		1 => 'D. melanogaster',
		2 => 'C. elegans',
		3 => 'S. cerevisiae'
	);
	private $majorloc_list = array (
		'cytoplasm' => 'Cytosol',
		'mitochondrion' => 'Mitochondrion',
		'nucleus' => 'Nucleus',
		'extracellular' => 'Extracellular',
		'secretory-pathway' => 'Secretory Pathway',
		'membrane' => 'Membrane',
	);
	private $verbose = false;
	//private $verbose_log = array();
	private $uniprot_root = 'http://www.uniprot.org/uniprot/';
	private $exptype = array(
		0 => 'Unknown',
		1 => 'Experimental',
		2 => 'Predicted'
	);


	/*	PROTEIN SEARCH
	 *	Find the proteins by their names or fragments of their names,
	 *	and filter the results by species, major localization and localization score.
	 *
	 *	The search string is splitted by new lines, and the fragments
	 *	are used as right-open needles in the
	 *	Protein.proteinName and NameToProtein.name
	 *	database fields (LIKE 'keyword1%' OR LIKE 'keyword2%').
	 *
	 *	The protein IDs found in Protein and NameToProtein are pooled together ($PID_POOL),
	 *	and filtered by localization and loc. score (from the LocalizationScore table)
	 *	if those are requested.
	 *	If this final protein ID pool contains only one protein, then the
	 *	interactor page is displayed, otherwise an intermediate protein selector page is shown.
	 *
	 *	This logic and the monolithic protein search ensures that
	 *	our old server can handle the load.
	*/
	public function proteinSearchAction($get_keyword)
    {
		// $get_keyword is the way to handle protein_search/PROTEIN_NAME type requests
		// = protein name from URL hooked on protein search
		if (!empty($get_keyword))
		{
			$_POST['fProtSearchKeyword'] = $get_keyword; // validated later
		}
		
		$request_m = $this->get('request')->getMethod();

		$T = array(
            'ls' => array(),
			'keyword' => '',
			'result_msg' => '',
			'loc_threshold' => 0.0,
			'uniprot_root' => $this->uniprot_root,
			'form_error_messages' => '',
        );
		
		// PREPARE THE SEARCH FORM
		// species in the form
		foreach ($this->species_list as $sp_code => $sp_name)
		{
			$T['species_list'][$sp_code] = array(
				'code' => $sp_code,
				'name' => $sp_name,
				'checked' => true
			);
			if ($request_m=='POST' and !isset($_POST['fProtSearchSp'][(string)$sp_code]))
			{
				$T['species_list'][(string)$sp_code]['checked'] = false;
			}
			// protein name from URL hooked on protein search
			if (!empty($get_keyword))
			{
				$_POST['fProtSearchSp'][(string)$sp_code] = true;
			}
		}
		
		// major locs in the form
		foreach ($this->majorloc_list as $mloc_code => $mloc_name)
		{
			$T['majorloc_list'][$mloc_code] = array(
				'code' => $mloc_code,
				'name' => $mloc_name,
				'checked' => true
			);
			$_SESSION['majorloc_list'][$mloc_code] = true;
			
			if ($request_m=='POST' and !isset($_POST['fProtSearchLoc'][(string)$mloc_code]))
			{
				$T['majorloc_list'][$mloc_code]['checked']
					= $_SESSION['majorloc_list'][$mloc_code]
					= false;
			}
			// protein name from URL hooked on protein search
			if (!empty($get_keyword))
			{
				$_POST['fProtSearchLoc'][$mloc_code] = true;
			}
		}
		
		// loc threshold in the form
		if (!empty($_POST['fProtSearchLocScore']) && 0<(float)$_POST['fProtSearchLocScore'] && (float)$_POST['fProtSearchLocScore']<=100)
		{
			$T['loc_score_slider_val']
				= $_SESSION['loc_score_slider_val']
				= (float)$_POST['fProtSearchLocScore'];
		} else {
			$T['loc_score_slider_val']
				= $_SESSION['loc_score_slider_val']
				= 0;
		}
		
		// inherit to interactors
		$T['inherit_filters_checked']
			= $_SESSION['inherit_filters_checked']
			= true;
		if (isset($_POST['fProtSearchSubmit']) && !isset($_POST['fProtSearchInheritFilters']))
		{
			$T['inherit_filters_checked']
				= $_SESSION['inherit_filters_checked']
				= false;
			}
		
		// PROTEIN SEARCH SUBMITTED
		if ($request_m=='POST' or !empty($get_keyword)) {
			$DB = $this->getDbConnection();
			$PID_POOL = []; // protein ID pool == protein IDs of the search result
			
			$keywords = array_filter(preg_split(
				"/\r\n|\n|\r/", // consider various platforms
				(isset($_POST['fProtSearchKeyword']) ? $_POST['fProtSearchKeyword'] : '')
			));
			
			// update template for the form
			$T['keyword'] = htmlspecialchars(strip_tags(implode(PHP_EOL, $keywords)));
			
			// PREPARE THE SEARCH CONDITIONS
			// SQL parameters: protein names as keywords
			if (!empty($keywords))
			{
				foreach ($keywords as $kk => $kwrd)
				{
					$keywords[$kk] = strtolower(trim($kwrd));
				}
			} else {
				$err[] = 'Please fill in a protein name.';
			}
			
			// too many protein names has been requested
			if (count($keywords)>100)
			{
				$T['result_msg'] = count($keywords)
					.' protein names were posted (maximum 100 are allowed).
						This would slow down our service, therefore the request has been cancelled.
						Please use a shorter query list, extract the data from the <a href="'
					.$this->generateUrl('DownloadCenterBundle_downloads')
					.'">downloads</a> or <a href="'
					.$this->generateUrl('ContactBundle_contact')
					.'">contact us</a> with specified details.';
				return $this->render(
					'ComppiProteinSearchBundle:ProteinSearch:index.html.twig',
					$T
				);
			}
			
			# SQL parameters: species
			$sql_cond_sp = [];
			if (!empty($_POST['fProtSearchSp']))
			{
				//$cond_sp = [];
				foreach ($_POST['fProtSearchSp'] as $fsp_code => $fsp_name)
				{
					if (isset($this->species_list[(int)$fsp_code]))
					{
						$sql_cond_sp[$fsp_code] = $fsp_code;
					}
				}
				//$sql_cond_sp = "'" . join("', '", $cond_sp) . "'";
			} else {
				$err[] = 'Please select at least one species.';
			}
			
			# SQL parameters: major localizations = compartments
			$sql_cond_mloc = [];
			if (!empty($_POST['fProtSearchLoc']))
			{
				foreach ($_POST['fProtSearchLoc'] as $fmloc_code => $fmloc_name)
				{
					if (isset($this->majorloc_list[$fmloc_code]))
					{
						$sql_cond_mloc[] = $fmloc_code; # discard keys!
					}
				}
				//$sql_cond_mloc = "'".join("', '", $cond_mloc)."'";
			} else {
				$err[] = 'Please select at least one subcellular compartment.';
			}
			
			# SQL parameters: localization threshold
			$loc_threshold = 0.0;
			if (!empty($_POST['fProtSearchLocScore']))
			{
				$T['loc_threshold'] = $loc_threshold = (float)$_POST['fProtSearchLocScore'];
				//$loc_threshold = $_POST['fProtSearchLocScore']/100;
			}
			
			// check for validation errors
			if (!empty($err))
			{
				//$err_msgs = implode(' ', $err);
				//$this->get('session')->setFlash('ps-errors', $err_msgs, $persist = false);
				$T['form_error_messages'] = implode("<br />", $err);
				
				return $this->render('ComppiProteinSearchBundle:ProteinSearch:index.html.twig', $T);
			}
			
			
			// FIND THE PROTEIN IDS FROM THE MAIN PROTEINS, THE SYNONYMS AND THE LOCALIZATIONS
			// the old server can't handle complex mysql queries -> separate them
			
			// Doctrine does not support multiple LIKE conditions, plus
			// unexplained Doctrine error if query builder is used
			// -> assemble the queries manually
			
			// $cond_for_pn_pal: [keyw1, keyw2, [1, 2, 0]]
/*			$cond_for_pn_pal = $keywords; // LIKE 'keyword%': parameters injected as single parameters instead of a single array
			$cond_for_pn_pal[] = $sql_cond_sp; // AND species IN(1,2): species are injected as a single array
			$cond_type_for_pn_pal = array_fill( // each keyword has the PARAM_STR type
				0,
				count($keywords),
				\PDO::PARAM_STR
			);
			$cond_type_for_pn_pal[] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY; */// species IDs are in an array
			
			// protein IDs from the strongest naming convention
			$r_prots_keyw_cond = [];
			foreach ($keywords as $kw)
			{
				$kw = $DB->quote($kw); // ke\yw'ord -> 'ke\\yw\'rd'
				$kw = substr_replace($kw, '%', 1, 0); // 'ke\\yw\'rd' -> '%ke\\yw\'rd'
				$kw = substr_replace($kw, '%', strlen($kw)-1, 0); // '%ke\\yw\'rd' -> '%ke\\yw\'rd%'
				$r_prots_keyw_cond[] = "LOWER(proteinName) LIKE " . $kw;
			}
			$r_prots = $DB->executeQuery(
				"SELECT id FROM Protein WHERE ("
					.implode(' OR ', $r_prots_keyw_cond)
					.") AND specieId IN(".implode(',', $sql_cond_sp).")"
			);
			if (!$r_prots)
			{
				die('Protein IDs by strongest naming convention query failed!');
			}
			$pids_by_strongest = $r_prots->fetchAll(\PDO::FETCH_COLUMN, 0);
			
			// protein IDs from the synonyms
			$r_n2p_keyw_cond = [];
			foreach ($keywords as $kw)
			{
				$kw = $DB->quote($kw); // ke\yw'ord -> 'ke\\yw\'rd'
				$kw = substr_replace($kw, '%', 1, 0); // 'ke\\yw\'rd' -> '%ke\\yw\'rd'
				$kw = substr_replace($kw, '%', strlen($kw)-1, 0); // 'ke\\yw\'rd' -> 'ke\\yw\'rd%'
				$r_n2p_keyw_cond[] = "LOWER(name) LIKE " . $kw;
			}
			$r_n2p = $DB->executeQuery(
				"SELECT proteinId FROM NameToProtein WHERE ("
					.implode(' OR ', $r_n2p_keyw_cond)
					.") AND specieId IN(".implode(',', $sql_cond_sp).")"
			);
			if (!$r_n2p)
			{
				die('Protein IDs by synonyms query failed!');
			}
			$pids_by_n2p = $r_n2p->fetchAll(\PDO::FETCH_COLUMN, 0);
			
			// PROTEIN ID POOL: MERGE THE UNIQUE PROTEIN IDS FROM PROTEIN NAMES
			$PID_POOL = array_unique(array_merge($pids_by_strongest, $pids_by_n2p));
			
			// protein IDs from the localizations table
			// filter only if *not* all major localizations are selected
			$pids_by_loc = [];
			if (!empty($PID_POOL) && !empty($sql_cond_mloc))
			{
				// IMPORTANT to prefilter for the already found protein IDs,
				// otherwise the loc-based protein pool would be HUGE
				$sql_cond_lf[]		= 'proteinId IN(?)';
				$sql_cond_val_lf[]	= $PID_POOL;
				$sql_cond_type_lf[]	= \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
				
				// filter for major localizations if not all were selected
				if (count($sql_cond_mloc)<count($this->majorloc_list))
				{
					$sql_cond_lf[]		= 'majorLocName IN(?)';
					$sql_cond_val_lf[]	= $sql_cond_mloc;
					$sql_cond_type_lf[]	= \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
				}
				
				// filter for localization score threshold
				if ($loc_threshold>0.0)
				{
					$sql_cond_lf[]		= 'score > ?';
					$sql_cond_val_lf[]	= strval($loc_threshold);
					$sql_cond_type_lf[]	= \PDO::PARAM_STR;
				}
				
				// assemble and execute the query if not only protein IDs are defined
				if (count($sql_cond_lf)>1 && count($sql_cond_val_lf)>1 && count($sql_cond_type_lf)>1)
				{
					$r_pids_by_loc = $DB->executeQuery(
						"SELECT proteinId FROM LocalizationScore WHERE "
							.implode(' AND ', $sql_cond_lf),
						$sql_cond_val_lf,
						$sql_cond_type_lf
					);
					
					if (!$r_pids_by_loc)
					{
						die('Protein IDs by localization query failed!');
					}
					
					// IMPORTANT: proteins should be filtered by localization and loc. score
					$PID_POOL = $r_pids_by_loc->fetchAll(\PDO::FETCH_COLUMN, 0);
				}
			}
			
			// use distinct protein IDs
			$PID_POOL = array_unique($PID_POOL);
			
			// INTERACTORS PAGE / PROTEIN SELECTOR PAGE / NOT FOUND
			// only 1 protein ID = exact match -> display the interators page
			if (count($PID_POOL)==1)
			{
				return $this->redirect($this->generateUrl(
					'ComppiProteinSearchBundle_interactors',
					array('comppi_id' => $PID_POOL[0]))
				);
			}
			// multiple protein IDs -> display the intermediate page to select one
			elseif (count($PID_POOL)>1)
			{
				$r_psr = $DB->executeQuery( // protein search results
					"
						SELECT
							n2p.name, n2p.specieId, n2p.proteinId, n2p.namingConvention, p.proteinName
						FROM
							NameToProtein n2p, Protein p
						WHERE
								n2p.proteinId=p.id
							AND p.id IN(?)
						GROUP BY p.proteinName
						ORDER BY p.proteinName DESC
					",
					array($PID_POOL),
					array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
				);
				if (!$r_psr)
					die('Protein search base on protein IDs failed!');
				
				while ($p = $r_psr->fetch(\PDO::FETCH_OBJ))
				{
					$pids[] = $p->proteinId;
					$T['ls'][] = array(
						'comppi_id' => $p->proteinId,
						'name' => $p->name,
						'name2' => $p->proteinName,
						'namingConvention' => $p->namingConvention,
						'species' => $this->species_list[$p->specieId],
						'uniprot_link' => $this->uniprot_root.$p->proteinName
					);
				}
				// attach the full protein names to the list
				if (!empty($pids)) {
					$full_names = $this->getProteinSynonyms($pids);
					foreach ($T['ls'] as $i=>$vals) {
						if (isset($full_names[$T['ls'][$i]['comppi_id']]['syn_fullname'])) {
							$T['ls'][$i]['full_name'] = $full_names[$T['ls'][$i]['comppi_id']]['syn_fullname'];
						} else {
							$T['ls'][$i]['full_name'] = 'N/A';
						}
					}
				} else {
					die("Protein IDs are missing for the full name query of the result selector!");
				}
				
				$T['found_num'] = count($T['ls']);
				
				return $this->render(
					'ComppiProteinSearchBundle:ProteinSearch:middlepage.html.twig',
					$T
				);
			}
			// no proteins were found
			else
			{
				$T['result_msg'] = 'No proteins were found with the current settings.';
			}
		}
		
		return $this->render('ComppiProteinSearchBundle:ProteinSearch:index.html.twig', $T);
	}


	/* QUERY PROTEIN AND ITS INTERACTORS
	 *
	 * Display the details of a query protein and its interactors.
	 * For the interactors a "skeleton" is built, and the details are
	 * appended to that skeleton.
	 *
	 * Many ugly workarounds are needed because the server is an old PC,
	 * for example the interactor details are fetched together and
	 * appended piece by piece, or the filtering of the details are done
	 * mostly in PHP.
	 * @TODO: merge all queries if a proper new server arrives.
	*/
	public function interactorsAction($comppi_id, $get_interactions)
	{
		$DB = $this->getDbConnection();
		$sp = $this->getSpeciesProvider();
		$spDescriptors = $sp->getDescriptors();
		$request_m = $this->get('request')->getMethod();
		$comppi_id = intval($comppi_id);
		$protein_ids = []; // collect the interactor IDs
		
		$T = array(
			'comppi_id' => $comppi_id,
			'ls' => array(),
			'protein_search_network_json' => array()
		);

		// FILTER: CONFIDENCE SCORE THRESHOLD
		// inherit from main search form: reset to defaults
		if (isset($_SESSION['inherit_filters_checked']) and !$_SESSION['inherit_filters_checked'])
		{
			$_POST['fIntFiltReset'] = true;
		}
		
		// set default value if the form was reset
		if (isset($_POST['fIntFiltReset']))
		{
			$T['conf_score_slider_val']
				= $_SESSION['conf_score_slider_val']
				= 0.0;
		}
		// set the requested value if the form was posted
		elseif (
			isset($_POST['fIntFiltConfScore']) &&
			0.0<=(float)$_POST['fIntFiltConfScore'] &&
			(float)$_POST['fIntFiltConfScore']<=1.00
		) {
			$T['conf_score_slider_val']
				= $_SESSION['conf_score_slider_val']
				= (float)$_POST['fIntFiltConfScore'];
		}
		// set from session
		elseif (isset($_SESSION['conf_score_slider_val'])) 
		{
			$T['conf_score_slider_val'] = $_SESSION['conf_score_slider_val'];
		}
		// set form and session to default
		else
		{
			$T['conf_score_slider_val']
				= $_SESSION['conf_score_slider_val']
				= 0.0;
		}

		// FILTER: MAJOR LOCALIZATIONS
		foreach ($this->majorloc_list as $mloc_code => $mloc_name)
		{
			// the defaults are always set = always "reset"
			$T['majorloc_list'][$mloc_code] = array(
				'code' => $mloc_code,
				'name' => $mloc_name,
				'checked' => true
			);
			// set the requested value if the form was posted
			if ($request_m=='POST' && !isset($_POST['fIntFiltReset'])) {
				if (isset($_POST['fIntFiltLoc'][(string)$mloc_code])) {
					$_SESSION['majorloc_list'][$mloc_code] = true;
				}
				else
				{
					$T['majorloc_list'][$mloc_code]['checked']
						= $_SESSION['majorloc_list'][$mloc_code]
						= false;
				}
			}
			// set from session
			elseif (
				!isset($_POST['fIntFiltReset']) &&
				isset($_SESSION['majorloc_list'][$mloc_code])
			) {
				$T['majorloc_list'][$mloc_code]['checked'] = $_SESSION['majorloc_list'][$mloc_code];
			}
			// else would be the default, already set above
		}
		$requested_major_locs = [];
		foreach ($T['majorloc_list'] as $mloc_name => $mloc_d)
		{
			if ($mloc_d['checked']) {
				$requested_major_locs[$mloc_name] = $mloc_name;
			}
		}
		$filter_by_mlocs = (count($requested_major_locs)==count($this->majorloc_list)
			? false : true);

		// FILTER: LOC SCORE THRESHOLD
		// set default value if the form was reset
		if (isset($_POST['fIntFiltReset']))
		{
			$T['loc_score_slider_val']
				= $_SESSION['loc_score_slider_val']
				= 0.0;
		}
		// set the requested value if the form was posted
		elseif (
			isset($_POST['fIntFiltLocScore']) &&
			0<=(float)$_POST['fIntFiltLocScore'] &&
			(float)$_POST['fIntFiltLocScore']<=1.00
		) {
			$T['loc_score_slider_val']
				= $_SESSION['loc_score_slider_val']
				= (float)$_POST['fIntFiltLocScore'];
		}
		// set from session
		elseif (isset($_SESSION['loc_score_slider_val']))
		{
			$T['loc_score_slider_val'] = $_SESSION['loc_score_slider_val'];
		}
		// set form and session to default
		else
		{
			$T['loc_score_slider_val']
				= $_SESSION['loc_score_slider_val']
				= 0.0;
		}

		// DETAILS OF THE REQUESTED PROTEIN
		$T['protein'] = $this->getProteinDetails(
			$comppi_id,
			($filter_by_mlocs ? $requested_major_locs : array()),
			$T['loc_score_slider_val']
		);
		// convert species ID to name
		$T['protein']['species'] = $this->species_list[$T['protein']['species']];
		
		// INTERACTORS
		// The threshold sliders operate in the [0,100] range with integers (for user conveninence),
		// but the confidence score (CS) is stored in the [0,1] range with floats.
		// Therefore a 100% CS on the front-end may mean anything from 0.99 to 1.0.
		// To address this, the threshold is lowered by 0.001,
		// therefore the error range of 0.01 is compressed to 0.001.
		// $conf_score_cond = round($T['conf_score_slider_val'], 3, PHP_ROUND_HALF_DOWN);
		$conf_score_cond = (float)$T['conf_score_slider_val'];
		if ($conf_score_cond>0.001) {
			$conf_score_cond = $conf_score_cond-0.001;
		}
		
		// number of all interactors of the protein
		$r_all_int_count = $DB->executeQuery(
			"SELECT COUNT(DISTINCT actorAId, actorBId) AS row_count
			FROM Interaction
			WHERE (actorAId = $comppi_id OR actorBId = $comppi_id)"
		);
		$T['all_interactors_count'] = $r_all_int_count->fetch(\PDO::FETCH_OBJ)->row_count;
		
		// interactors according to the current settings
		$r_interactors = $DB->executeQuery(
			"SELECT DISTINCT
				i.id AS iid, i.sourceDb, i.pubmedId,
				cs.score as confScore,
				p.id as pid, p.proteinName as name, p.proteinNamingConvention as namingConvention
			FROM Interaction i
			LEFT JOIN Protein p ON p.id=IF(actorAId = $comppi_id, i.actorBId, i.actorAId)
			LEFT JOIN ConfidenceScore cs ON i.id=cs.interactionId
			WHERE (actorAId = $comppi_id OR actorBId = $comppi_id)
			" . (!empty($T['conf_score_slider_val'])
				? " AND cs.score >= ".$conf_score_cond // safe from SQL injection
				: '')
			. "
			ORDER BY cs.score DESC"
		);
		//	LIMIT ".$this->search_result_per_page);
		if (!$r_interactors)
			die('Interactor query failed!');

		// there may be multiple interactions between the same interactors
		$confScoreAvg = 0.0;
		$confCounter = 0;

		// interactors skeleton: data keyed by the ComPPI ID of the interactor protein
		while ($i = $r_interactors->fetch(\PDO::FETCH_OBJ))
		{
			$T['ls'][$i->pid]['prot_id'] = $i->pid;
			$T['ls'][$i->pid]['prot_name'] = $i->name;
			$T['ls'][$i->pid]['prot_naming'] = $i->namingConvention;
			//if ($i->namingConvention=='UniProtKB-AC')
			$T['ls'][$i->pid]['uniprot_outlink'] = $this->uniprot_root.$i->name;
			$T['ls'][$i->pid]['orig_conf_score'] = $i->confScore;
			$T['ls'][$i->pid]['confScore'] = round($i->confScore, 3);//*100;
			// note that there may be multiple source DBs and PubMed IDs
			$T['ls'][$i->pid]['int_source_db'][$i->sourceDb] = $i->sourceDb;
			$pml = $this->linkToPubmed($i->pubmedId);
			$T['ls'][$i->pid]['int_pubmed_link'][$pml] = $pml;

			$protein_ids[$i->pid] = $i->pid;
			//$interaction_ids[$i->iid] = $i->iid;
		}

		// @TODO: letölthető dataset
		//if ($get_interactions) {
		//	return $this->forward(
		//		'DownloadCenterBundle:DownloadCenter:serveInteractions',
		//		array('species' => array('abbr' => 'all', 'id' => -1),
		//			  'interaction_ids' => $interaction_ids)
		//	);
		//}

		if (!empty($protein_ids)) {
			// localizations for the interactor
			$protein_locs = $this->getProteinLocalizations(
				$protein_ids,
				($filter_by_mlocs ? $requested_major_locs : array()),
				$T['loc_score_slider_val']
			);		

			// synonyms for the interactor
			$protein_synonyms = $this->getProteinSynonyms($protein_ids);

			// interactors: update the existing skeleton (therefore reference is needed),
			// also create the nodes array for visualization
			$json_nodes = array();
			foreach($T['ls'] as $pid => &$actor)
			{
				// keep the interaction e if no filtering is set, or
				// 
				if ($filter_by_mlocs and empty($protein_locs[$pid]))
				{
					unset($T['ls'][$pid]);
				}
				else
				{
					if (empty($protein_locs[$pid])) {
						$protein_locs[$pid] = array();
					}
					
					// attach localizations to interactors
					$actor['locs'] = $protein_locs[$pid];
					
					// synonyms to interactors
					if (!empty($protein_synonyms[$pid]['syn_fullname']))
						$actor['syn_fullname'] =  $protein_synonyms[$pid]['syn_fullname'];
					if (!empty($protein_synonyms[$pid]['synonyms']))
						$actor['synonyms'] = $protein_synonyms[$pid]['synonyms'];
					//$actor['syn_namings'] = (empty($protein_synonyms[$pid]['syn_namings']) ? array() : $protein_synonyms[$pid]['syn_namings']);
					
					// add the confidence score to the average
					// only if interaction is kept
					$confScoreAvg += (float)$actor['orig_conf_score'];
					$confCounter++;
					
					// network visualization: collect node data
					$json_nodes[] = array(
						'comppi_id' => $pid,
						'name' => $T['ls'][$pid]['prot_name']
					);
				}
			}
			
			// downloadable dataset
			if ($get_interactions=='download') {
				$dl_filename = 'comppi--interactors_of_' . $T['protein']['name'] . '.txt';
				session_cache_limiter('none');
				
				$response = new Response();
				$response->headers->set('Content-Description', 'File Transfer');
				$response->headers->set('Cache-Control', 'no-cache');
				$response->headers->set('Pragma', 'public');
				$response->headers->set('Content-Type', 'application/octet-stream');
				$response->headers->set('Content-Transfer-Encoding', 'binary');
				$response->headers->set('Expires', '0');
				$response->headers->set('Content-Disposition', 'attachment; filename="'.$dl_filename.'"');
				// stream_copy_to_stream() or file_get_contents() would be nicer, but that is a memory hog
				// Symfony2.1's StreamedResponse is not available in 2.0
				$response->sendHeaders();
				ob_clean();
				flush();
				
				// @see http://comppi.linkgroup.hu/help/downloads
				// header
				echo 'Interactor'. "\t"
					.'Canonical Name' . "\t"
					.'Naming Convention' . "\t"
					.'Major Loc With Loc Score' . "\t"
					.'Minor Loc' . "\t"
					.'Loc Experimental System Type' . "\t"
					.'Loc Source DB' . "\t"
					//.'localization_pubmed_id' . "\t"
					.'Taxonomy ID'
					."\n";
				
				// key protein
				$tax_id = $this->species_list[$T['protein']['specieId']];
				if (!empty($T['protein']['locs'])) {
					$major_locs = array();
					$minor_locs = array();
					$exp_sys_type = array();
					$source_dbs = array();
					//$pubmed_ids = array();
					
					foreach ($T['protein']['locs'] as $l) {
						$major_locs[] = $l['large_loc'] . '('.$l['loc_score'].')';
						$minor_locs[] = $l['go_code'];
						$exp_sys_type[] = $l['loc_exp_sys'];
						$source_dbs[] = $l['source_db'];
						//$pubmed_ids[] = $l['loc_exp_sys_type'];
					}
					
					$major_locs = implode('|', array_unique($major_locs));
					$minor_locs = implode('|', $minor_locs);
					$exp_sys_type = implode('|', $exp_sys_type);
					$source_dbs = implode('|', $source_dbs);
					//$pubmed_ids = implode('|', $pubmed_ids);
				} else {
					$major_locs = '';
					$minor_locs = '';
					$exp_sys_type = '';
					$source_dbs = '';
					//$pubmed_ids = '';
				}
				
				echo $T['protein']['name'] . "\t"
					.(!empty($T['protein']['fullname']) ? $T['protein']['fullname'] : '') . "\t"
					.$T['protein']['naming'] . "\t"
					.$major_locs . "\t"
					.$minor_locs . "\t"
					.$exp_sys_type . "\t"
					.$source_dbs . "\t"
					//.$pubmed_ids . "\t"
					.$tax_id
					."\n";

				// interactors
				foreach ($T['ls'] as $pid => $d) {
					if (!empty($d['locs'])) {
						$major_locs = array();
						$minor_locs = array();
						$exp_sys_type = array();
						$source_dbs = array();
						//$pubmed_ids = array();
						
						foreach ($d['locs'] as $l) {
							$major_locs[] = $l['large_loc'] . '('.$l['loc_score'].')';
							$minor_locs[] = $l['go_code'];
							$exp_sys_type[] = $l['loc_exp_sys'];
							$source_dbs[] = $l['source_db'];
							//$pubmed_ids[] = $l['loc_exp_sys_type'];
						}
						
						$major_locs = implode('|', array_unique($major_locs));
						$minor_locs = implode('|', $minor_locs);
						$exp_sys_type = implode('|', $exp_sys_type);
						$source_dbs = implode('|', $source_dbs);
						//$pubmed_ids = implode('|', $pubmed_ids);
					} else {
						$major_locs = '';
						$minor_locs = '';
						$exp_sys_type = '';
						$source_dbs = '';
						//$pubmed_ids = '';
					}
					
					echo $d['prot_name'] . "\t"
						.(!empty($d['syn_fullname']) ? $d['syn_fullname'] : '') . "\t"
						.$d['prot_naming'] . "\t"
						.$major_locs . "\t"
						.$minor_locs . "\t"
						.$exp_sys_type . "\t"
						.$source_dbs . "\t"
						//.$pubmed_ids . "\t"
						.$tax_id
						."\n";
				}
				
				exit();
			}
			
			// network visualization: assemble network
			$json_links = array();
			$key_node_id = count($json_nodes);
			foreach ($json_nodes as $nid => $nd) {
				//$json_nodes[$nid]['index'] = $nid;
				$json_links[] = array(
					'source' => $key_node_id, // source is always the key protein
					'target' => $nid, // target is an interactor
					'weight' => $T['ls'][$nd['comppi_id']]['confScore'] // weight: confidence score from 'interactors skeleton'
				);
			}
			// add the requested (key) protein only now to prevent self-loop
			$json_nodes[$key_node_id] = array(
				'comppi_id' => $comppi_id, // ID of the key protein
				'name' => $T['protein']['name']
			);
			
			$T['protein_search_network_json'] = json_encode(array(
				'nodes' => $json_nodes,
				'links' => $json_links
			));
		}

		$T['protein']['interactionNumber'] = count($T['ls']);
		if ($T['protein']['interactionNumber']) {
			$T['protein']['avgConfScore'] = round($confScoreAvg/$confCounter, 3);//*100;
		} else {
			$T['protein']['avgConfScore'] = false;
		}

		return $this->render('ComppiProteinSearchBundle:ProteinSearch:interactors.html.twig',$T);
	}


	public function autocompleteAction($keyword)
	{
		$DB = $this->getDbConnection();
		$r_i = $DB->executeQuery(
		"
			SELECT name
			FROM ProteinName
			WHERE name LIKE ?
			ORDER BY LENGTH(name)
			LIMIT 100
			",
			array("%$keyword%")
		);
		if (!$r_i) { return new Response(json_encode(array('QUERY FAILED'))); }
		$list = $r_i->fetchAll(\PDO::FETCH_COLUMN, 0);

        return new Response(json_encode($list));
	}


	private function getProteinDetails($comppi_id, $requested_major_locs = array(), $loc_prob = 0)
	{
		$DB = $this->getDbConnection();
		$r_p = $DB->executeQuery("SELECT proteinName AS name, proteinNamingConvention AS naming, specieId FROM Protein WHERE id=?", array($comppi_id));
		if (!$r_p) throw new \ErrorException('Protein query failed!');

		$prot_details = $r_p->fetch(\PDO::FETCH_ASSOC);
		$prot_details['species'] = $prot_details['specieId']; // @TODO: map name to id
		$prot_details['locs'] = $this->getProteinLocalizations(array($comppi_id), $requested_major_locs, $loc_prob);
		$prot_details['locs'] = (!empty($prot_details['locs'][$comppi_id]) ? $prot_details['locs'][$comppi_id] : array());

		$syns = $this->getProteinSynonyms(array($comppi_id));
		$prot_details['synonyms'] = (!empty($syns[$comppi_id]['synonyms']) ? $syns[$comppi_id]['synonyms'] : []);
		$prot_details['fullname'] = (!empty($syns[$comppi_id]['syn_fullname']) ? $syns[$comppi_id]['syn_fullname'] : '');
		$prot_details['uniprot_link'] = (!empty($this->uniprot_root.$prot_details['name']) ? $this->uniprot_root.$prot_details['name'] : '');

		return $prot_details;
	}


	/* PROTEIN LOCALIZATIONS */
	private function getProteinLocalizations(
		$comppi_ids,
		$requested_major_locs = array(),
		$loc_score_threshold = 0
	) {
		$DB = $this->getDbConnection();
		
		if (!empty($requested_major_locs)) {
			foreach ($requested_major_locs as $mloc)
			{
				$requested_major_locs[$mloc] = $mloc; // convert to speed up: isset is faster than in_array
				$mloc_cond[] = $DB->quote($mloc);
			}
		}
		
		// The threshold sliders operate in the [0,100] range with integers (for user conveninence),
		// but the loc. score is stored in the [0,1] range with floats.
		// Therefore a 100% loc on the front-end may mean anything from 0.99 to 1.0.
		// To address this, the threshold is lowered by 0.001,
		// therefore the error range of 0.01 is compressed to 0.001.
		// $loc_prob = round($loc_score_threshold, 3, PHP_ROUND_HALF_DOWN);
		$loc_prob = (float)$loc_score_threshold;
		// we need to lower a bit the threshold,
		// otherwise locs with 100% loc score won't appear
		if ($loc_prob>0.0) {
			$loc_prob = $loc_prob-0.001;
		}

		$sql_ls = 'SELECT
				proteinId as pid, majorLocName, score
			FROM LocalizationScore
			WHERE
				proteinId IN ('.join(',', $comppi_ids).')';
		if (!empty($requested_major_locs))
		{
			$sql_ls .= " AND majorLocName IN (".join(',', $mloc_cond).")";
		}
		if (!empty($loc_score_threshold))
		{
			$sql_ls .= " AND score >= ".$loc_prob;
		}
		$this->verbose ? $this->verbose_log[] = $sql_ls : '';

		if (!$r_ls = $DB->executeQuery($sql_ls))
			die('LocalizationScore query failed!');

		$loc_scores = array();
		while ($ls = $r_ls->fetch(\PDO::FETCH_OBJ))
		{
			$loc_scores[$ls->pid][$ls->majorLocName] = $ls->score;
		}

		// do NOT use protein IDs from the 'LocalizationScore' query above
		// it breaks the behavior of displaying localizations
		// with partially missing data
		$sql_pl = '
			SELECT
				ptl.proteinId as pid, ptl.localizationId AS locId,
				ptl.sourceDb, ptl.pubmedId,
				lt.name as minorLocName, lt.goCode, lt.majorLocName,
				st.name AS exp_sys, st.confidenceType AS exp_sys_type
			FROM
				ProtLocToSystemType pltst,
				SystemType st,
				ProteinToLocalization ptl
			LEFT JOIN
				Loctree lt ON ptl.localizationId=lt.id
			WHERE ptl.id=pltst.protLocId
				AND pltst.systemTypeId=st.id
				AND ptl.proteinId IN ('.join(',', $comppi_ids).')';
		$this->verbose ? $this->verbose_log[] = $sql_pl : '';

		if (!$r_pl = $DB->executeQuery($sql_pl))
			die('ProteinToLocalization query failed!');

		$i = 0;
		while ($p = $r_pl->fetch(\PDO::FETCH_OBJ))
		{
			$i++;
			$tmp = array(); // buffer
			$add = false; // flag determining if the row will be kept

			// assemble the current localization data
			$tmp['source_db'] = $p->sourceDb;
			$tmp['pubmed_link'] = $this->linkToPubmed($p->pubmedId);
			$tmp['loc_exp_sys'] = $this->exptype[$p->exp_sys_type] . ': ' . $p->exp_sys;
			$tmp['loc_exp_sys_type'] = $p->exp_sys_type;
			if (!empty($p->minorLocName)) {
				$tmp['small_loc'] = ucfirst($p->minorLocName);
				$tmp['go_code'] = ucfirst($p->goCode);
			} else {
				$tmp['small_loc'] = 'N/A';
				$tmp['go_code'] = 'N/A';
			}
			if (!empty($p->majorLocName)) {
				$tmp['large_loc'] = ucfirst($p->majorLocName) /*. '['.$p->locId.']'*/;
				if (!empty($loc_scores[$p->pid][$p->majorLocName])) {
					$tmp['loc_score'] = round($loc_scores[$p->pid][$p->majorLocName], 3);//*100;
				} else {
					$tmp['loc_score'] = 0.0;
				}
			} else {
				$tmp['large_loc'] = 'N/A';
				$tmp['loc_score'] = 0.0;
			}

			// filter the results if needed
			if (empty($requested_major_locs) && empty($loc_score_threshold))
			{
				// no filtering requirements -> add
				$add = true;
			}
			elseif (empty($loc_score_threshold) && isset($requested_major_locs[$p->majorLocName])) // isset is faster than in_array
			{
				// no loc score, but major loc is set -> add
				$add = true;
			}
			elseif (empty($requested_major_locs) && $tmp['loc_score']>$loc_prob)
			{
				// no major loc, but loc score is set -> add
				$add = true;
			}
			elseif (
				!empty($requested_major_locs) &&
				!empty($loc_score_threshold) &&
				isset($requested_major_locs[$p->majorLocName]) &&
				$tmp['loc_score']>$loc_prob
			) {
				// both major loc and loc score requirements are fulfilled
				$add = true;
			}
			
			// add the final assembled localization row
			if ($add)
			{
				$pl[$p->pid][$i] = $tmp;
			}
			
			unset($tmp);
		}

		$this->verbose ? $this->verbose_log[] = count($pl).' protein locations found' : '';

		return (!empty($pl) ? $pl : array());
	}


	/*	Collect the synonyms for a given set of proteins, and group these by protein ID.

		@param $comppi_ids: array of ints, the protein IDs
		@return array

		Example:
		>>> this->getProteinSynonyms($comppi_ids)
		... synonyms = array(
				'syn_fullname' = 'UniprotFull name',
				'synonyms' => array('Syn1', 'syn2')
			)
	*/
	private function getProteinSynonyms($comppi_ids)
	{
		$DB = $this->getDbConnection();
		$sql_syn = "SELECT proteinId AS pid, name, namingConvention FROM NameToProtein WHERE proteinId IN(".join(',', $comppi_ids).")";
		$this->verbose ? $this->verbose_log[] = $sql_syn : '';

		if (!$r_syn = $DB->executeQuery($sql_syn))
			throw new \ErrorException('getProteinSynonyms query failed!');

		$syns = array();
		while ($s = $r_syn->fetch(\PDO::FETCH_OBJ))
		{
			if ($s->namingConvention=='UniProtFull') {
				$syns[$s->pid]['syn_fullname'] = $s->name; // full name highlighted...
			} else {
				$syns[$s->pid]['synonyms'][] = $s->name.'&nbsp;('.$s->namingConvention.')';
			}
		}

		return $syns;
	}


	private function linkToPubmed($pubmed_uid)
	{
		return 'http://www.ncbi.nlm.nih.gov/pubmed/'.$pubmed_uid;
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

		$this->get('database_connection')->getConfiguration()->setSQLLogger(null);
		return $this->DB;
	}

	private function getLocalizationTranslator()
	{
		if (!$this->localizationTranslator)
			$this->localizationTranslator = $this->get('comppi.build.localizationTranslator');

		return $this->localizationTranslator;
	}


	/**
	 * Current test protein: P04637 = ComPPI ID: 17387
	 * Gets the first neighbours of a node with their connections to earch other (or optionally all of their interactions). Writes a tab-separated text file with rows like: "locA.nodeA'. "\t" .'locB.nodeB\n".
	 * @param int $comppi_id The ComPPI ID of the starting node */
	public function subgraphAction($comppi_id)
	{
		$joined_node_names = true; // node name = major_loc.protein_name (to display in Cytoscape for example)
		$interaction_count = 0;

		$interaction_sql = "INSERT INTO Interaction (id, actorAId, actorBId) VALUES \n";
		$interaction_rows = array();
		$protein_sql = "INSERT INTO Protein (id, specieId, proteinName) VALUES \n";
		$protein_rows = array();
		$prot_to_loc_sql = "INSERT INTO ProteinToLocalization (id, proteinId, localizationId) VALUES \n";
		$prot_to_loc_rows = array();
		$protloc_to_systype_sql = "INSERT INTO ProtLocToSystemType (protLocId, systemTypeId) VALUES \n";
		$protloc_to_systype_rows = array();
		$systype_sql = "INSERT INTO SystemType (id, confidenceType) VALUES \n";
		$systype_rows = array();

		// GET THE STAR-SHAPED NETWORK OF THE REQUESTED PROTEIN AND ITS FIRST NEIGHBOURS
		$DB = $this->getDbConnection();
		$r_actor_ids = $DB->executeQuery(
			"SELECT DISTINCT
				i.id AS iid,
				IF(actorAId=?, actorBId, actorAId) as actorId,
				p.id as proteinId, p.proteinName
			FROM Interaction i
			LEFT JOIN Protein p ON p.id=IF(actorAId = ?, i.actorBId, i.actorAId)
			WHERE actorAId = ? OR actorBId = ?",
			array($comppi_id, $comppi_id, $comppi_id, $comppi_id)
		);

		while($actor = $r_actor_ids->fetch(\PDO::FETCH_OBJ)) {
			$first_neighbour_ids[$actor->actorId] = $actor->actorId;
			$interaction_rows[$actor->iid] = '('.$actor->iid.', '.$comppi_id.', '.$actor->actorId.')';
			$protein_rows[$actor->proteinId] = '('.$actor->proteinId.", 0, '".$actor->proteinName."')";
		}

		// GET THE INTERACTIONS AMONG THE FIRST NEIGHBOURS
		$sql_neighbour_links = "SELECT DISTINCT i.id as iid, p1.id as p1id, p1.proteinName as proteinA, p2.id as p2id, p2.proteinName as proteinB, ptl1.id as protLocAId, ptl1.localizationId as locAId, ptl2.id as protLocBId, ptl2.localizationId as locBId, st1.id as sysTypeAId, st1.confidenceType as confTypeA, st2.id as sysTypeBId, st1.confidenceType as confTypeB
		FROM Interaction i
		LEFT JOIN Protein p1 ON p1.id=i.actorAId
		LEFT JOIN ProteinToLocalization ptl1 ON p1.id=ptl1.proteinId
		LEFT JOIN ProtLocToSystemType ptst1 ON ptl1.id=ptst1.protLocId
		LEFT JOIN SystemType st1 ON ptst1.systemTypeId=st1.id
		LEFT JOIN Protein p2 ON p2.id=i.actorBId
		LEFT JOIN ProteinToLocalization ptl2 ON p2.id=ptl2.proteinId
		LEFT JOIN ProtLocToSystemType ptst2 ON ptl2.id=ptst2.protLocId
		LEFT JOIN SystemType st2 ON ptst2.systemTypeId=st2.id
		WHERE (i.actorAId IN(".join(',', $first_neighbour_ids).") OR i.actorBId IN(".join(',', $first_neighbour_ids)."))
		GROUP BY ptl1.localizationId, ptl2.localizationId";

		$sql_neighbour_links_for_negative_set = "SELECT DISTINCT i.id as iid, p1.id as p1id, p1.proteinName as proteinA, p2.id as p2id, p2.proteinName as proteinB, ptl1.id as protLocAId, ptl1.localizationId as locAId, ptl2.id as protLocBId, ptl2.localizationId as locBId, st1.id as sysTypeAId, st1.confidenceType as confTypeA, st2.id as sysTypeBId, st1.confidenceType as confTypeB
		FROM Interaction i
		LEFT JOIN Protein p1 ON p1.id=i.actorAId
		LEFT JOIN ProteinToLocalization ptl1 ON p1.id=ptl1.proteinId
		LEFT JOIN ProtLocToSystemType ptst1 ON ptl1.id=ptst1.protLocId
		LEFT JOIN SystemType st1 ON ptst1.systemTypeId=st1.id
		LEFT JOIN Protein p2 ON p2.id=i.actorBId
		LEFT JOIN ProteinToLocalization ptl2 ON p2.id=ptl2.proteinId
		LEFT JOIN ProtLocToSystemType ptst2 ON ptl2.id=ptst2.protLocId
		LEFT JOIN SystemType st2 ON ptst2.systemTypeId=st2.id
		WHERE (i.actorAId IN(".join(',', $first_neighbour_ids).") OR i.actorBId IN(".join(',', $first_neighbour_ids)."))
		GROUP BY ptl1.localizationId, ptl2.localizationId LIMIT 20000";

		//die($sql_neighbour_links);

		$r_neighbour_links = $DB->executeQuery($sql_neighbour_links);

		$locs = $this->getLocalizationTranslator();

		while($link = $r_neighbour_links->fetch(\PDO::FETCH_OBJ)) {
			$large_loc_a = (empty($link->locAId) ? "N/A" : $locs->getLargelocById($link->locAId));
			$large_loc_b = (empty($link->locBId) ? "N/A" : $locs->getLargelocById($link->locBId));

			// take those and only those interactions,
			// where the interactors are in the same known localization -> POSITIVE DATA SET
			// for NEGATIVE DATA SET: $large_loc_a != $large_loc_b
			if ($large_loc_a == $large_loc_b AND $large_loc_a != 'N/A') {
				$interaction_count++;

				// Interaction
				$interaction_tmp_row = '('.$link->iid.', '.$link->p1id.', '.$link->p2id.')';
				if (!in_array($interaction_tmp_row, $interaction_rows))
					$interaction_rows[$link->iid] = $interaction_tmp_row;

				// Protein
				$protein_rows[$link->p1id] = '('.$link->p1id.", 0, '".$link->proteinA."')";
				$protein_rows[$link->p2id] = '('.$link->p2id.", 0, '".$link->proteinB."')";

				// ProteinToLocalization
				$prot_to_loc_rows[$link->protLocAId] = '('.$link->protLocAId.', '.$link->p1id.', '.$link->locAId.')';
				$prot_to_loc_rows[$link->protLocBId] = '('.$link->protLocBId.', '.$link->p2id.', '.$link->locBId.')';

				// ProtLocToSystemType
				$protloc_to_systype_rows[$link->protLocAId] = '('.$link->protLocAId.', '.$link->sysTypeAId.')';
				$protloc_to_systype_rows[$link->protLocBId] = '('.$link->protLocBId.', '.$link->sysTypeBId.')';

				// SystemType
				$systype_rows[$link->sysTypeAId] = '('.$link->sysTypeAId.', '.$link->confTypeA.')';
				$systype_rows[$link->sysTypeBId] = '('.$link->sysTypeBId.', '.$link->confTypeB.')';
			}
		}

		$interaction_sql .= join(", \n", $interaction_rows);
		$protein_sql .= join(", \n", $protein_rows);
		$prot_to_loc_sql .= join(", \n", $prot_to_loc_rows);
		$protloc_to_systype_sql .= join(", \n", $protloc_to_systype_rows);
		$systype_sql .= join(", \n", $systype_rows);

		$file_tbl_structure = "/var/www/comppi/comppi_positive_dataset_structure-trimmed.sql";
		$fp_structure = fopen($file_tbl_structure, "r");
		$tbl_structures = fread($fp_structure, filesize($file_tbl_structure));
		fclose($fp_structure);

		$filename = date("YmdHis").'_subgraph_of_'.$comppi_id.".sql";
		$fp = fopen("/var/www/comppi/$filename", "a");
		fwrite($fp,
			 $tbl_structures."\n"
			.$interaction_sql.";\n\n"
			.$protein_sql.";\n\n"
			.$prot_to_loc_sql.";\n\n"
			.$protloc_to_systype_sql.";\n\n"
			.$systype_sql.";\n\n"
		);
		fclose($fp);
		chmod("/var/www/comppi/$filename", 0777);

		return new Response("[ OK ]<br>First neighbours: ".$r_actor_ids->rowCount()."<br>Interactions: $interaction_count");
	}
}
