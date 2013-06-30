<?php

namespace Comppi\DownloadCenterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class DownloadCenterController extends Controller
{
    private $releases_dir = './dbreleases/'; // trailing slash is important!
    private $downloads_dir = './download/';
    private $current_db_sql = 'comppi.sql';
	private $pubmed_link = 'http://www.ncbi.nlm.nih.gov/pubmed/';
	private $zipped_outputs = false; // UNTESTED WITH TRUE!
	private $demo_mode = false; // limit output to the first 1000 rows
    
    public function currentReleaseGuiAction()
    {
		$sp = $this->get('comppi.build.specieProvider');
        $T = array();
		
		$path = $this->releases_dir.$this->current_db_sql;
		$T['dset_all_size'] = number_format((filesize($path)/1048576), 2, '.', ' '); // get it in MB
        $T['dset_all_mtime'] = date("Y-m-d. H:i:s", filemtime($path));
		
		$T['need_all'] = 1;
		$T['need_hs'] = 0;
		$T['need_dm'] = 0;
		$T['need_ce'] = 0;
		$T['need_sc'] = 0;
		
		$T['need_cytoplasm'] = (isset($_POST['fDlSpecSc']) ? 1 : 0);
		$T['need_mito'] = (isset($_POST['fDlSpecSc']) ? 1 : 0);
		$T['need_nucleus'] = (isset($_POST['fDlSpecSc']) ? 1 : 0);
		$T['need_ec'] = (isset($_POST['fDlSpecSc']) ? 1 : 0);
		$T['need_secr'] = (isset($_POST['fDlSpecSc']) ? 1 : 0);
		$T['need_plasmembr'] = (isset($_POST['fDlSpecSc']) ? 1 : 0);
		
		$sp = $this->speciesProvider = $this->get('comppi.build.specieProvider');
		$request = $this->getRequest();
		
		if (isset($_SESSION['messages']['compartment_error'])) {
			$T['error_msgs'] = $_SESSION['messages']['compartment_error'];
			unset($_SESSION['messages']['compartment_error']);
		}
		elseif ($request->getMethod() == 'POST')
		{
			if (!isset($_POST['fDlSpec']) or $_POST['fDlSpec']=='all') {
				$species = array('abbr' => 'all', 'id' => -1);
			} else {
				// if species is forged, getSpecieByAbbreviation will throw an exception...
				$species_descriptor = $sp->getSpecieByAbbreviation($_POST['fDlSpec']);
				$species = array('abbr' => $_POST['fDlSpec'], 'id' => $species_descriptor->id);
			}
			
			
			//var_dump($_POST);
			
			switch ($_POST['fDlSet']) {
				case 'all':
					return $this->forward('DownloadCenterBundle:DownloadCenter:serveAllData');
					break;
				case 'comp':
					return $this->forward('DownloadCenterBundle:DownloadCenter:serveComparmentalizedData', array('species' => $species));
					break;
				case 'detailedint':
					return $this->forward('DownloadCenterBundle:DownloadCenter:serveDetailedInteractions', array('species' => $species));
					break;
				case 'int':
					return $this->forward('DownloadCenterBundle:DownloadCenter:serveInteractions', array('species' => $species));
					break;
				case 'protnloc':
					return $this->forward('DownloadCenterBundle:DownloadCenter:serveProteinsAndLocs', array('species' => $species));
					break;
			}
		}
		
        return $this->render('DownloadCenterBundle:DownloadCenter:currentrelease.html.twig', $T);
    }
    
    public function previousReleasesGuiAction()
    {
        $T = array(
            'ls' => array()
        );
        
        $d = dir($this->releases_dir);
        while (false !== ($entry = $d->read())) {
            if ($entry!='.' && $entry!='..' && $entry!=$this->current_db_sql)
            {
                $entry = array(
                    'file' => $entry,
                    'size' => number_format((filesize($this->releases_dir.$entry)/1048576), 2, '.', ' '),
                    'mtime' => date("Y-m-d. H:i:s", filemtime($this->releases_dir.$entry))
                );
                $T['ls'][$entry['mtime']] = $entry;
            }
        }
        $d->close();
        
        ksort($T['ls']);
        
        return $this->render('DownloadCenterBundle:DownloadCenter:previousreleases.html.twig', $T);
    }
    
    public function previousReleaseAction($file)
    {
        return $this->serveFile($this->releases_dir.$file); // $this->releases_dir prevents cross-site serving!
    }

	
	public function serveAllDataAction()
	{	
		return $this->serveFile($this->releases_dir.$this->current_db_sql);
	}
	
	
	public function serveComparmentalizedDataAction($species)
	{
		// we define which compartments are needed... (keys are the same as to be found in $locs->getLargelocs(), see buildComparmentalizedData() method)
		$compartments = array();
		isset($_POST['fDlMLocCytoplasm']) ? $compartments['cytoplasm'] = true : '';
		isset($_POST['fDlMLocMito']) ? $compartments['mitochondrion'] = true : '';
		isset($_POST['fDlMLocNucleus']) ? $compartments['nucleus'] = true : '';
		isset($_POST['fDlMLocEC']) ? $compartments['extracellular'] = true : '';
		isset($_POST['fDlMLocSecr']) ? $compartments['secretory-pathway'] = true : '';
		isset($_POST['fDlMLocPlasMembr']) ? $compartments['membrane'] = true : '';

		if (empty($compartments)) {
			$_SESSION['messages']['compartment_error'] = "Please select at least one compartment!";
			return $this->forward('DownloadCenterBundle:DownloadCenter:currentReleaseGui');
		}
		
		$file = $this->downloads_dir.'comppi--interactions_by_compartments--'.join('_', array_keys($compartments)).'--'.$species['abbr'].'.csv';
        
		if (file_exists($file.'.zip')) {
			return $this->serveFile($file.'.zip');
		} elseif (file_exists($file)) {
            return $this->serveFile($file);
		} else {
			$this->buildComparmentalizedData(basename($file), $species['id'], $compartments);
			return $this->serveFile($file);
		}
	}
	
	
	private function buildComparmentalizedData($filename, $species_id, $compartments)
	{
		$this->setTimeout(240);
		$locs = $this->get('comppi.build.localizationTranslator');
		$DB = $this->get('database_connection');
		
		// iterate the requested major locs to get all the localization IDs which belong to them
		$major_locs = $locs->getLargelocs();
		$loc_id_pool = array();
		foreach($compartments as $comp_name => $needed) {
			$loc_id_pool = array_merge($loc_id_pool, $major_locs[$comp_name]);
		}
		$loc_id_pool = array_unique($loc_id_pool);
		
		// INTERACTIONS IN THE REQUESTED COMPARTMENTS
		$sql_i = "SELECT
			i.id as iid, i.sourceDb, i.pubmedId,
			GROUP_CONCAT(DISTINCT cs.score ORDER BY cs.score) as confScore,
			GROUP_CONCAT(DISTINCT ist.name ORDER BY ist.name) AS expSysType,
			p1.id as p1id, p1.proteinName as actorA,
			GROUP_CONCAT(DISTINCT ptl1.localizationId ORDER BY ptl1.localizationId) as locAId,
			GROUP_CONCAT(DISTINCT ptl1.sourceDb ORDER BY ptl1.sourceDb) as locASourceDb,
			GROUP_CONCAT(DISTINCT ptl1.pubmedId ORDER BY ptl1.pubmedId) as locAPubmedId,
			p2.id as p2id, p2.proteinName as actorB,
			GROUP_CONCAT(DISTINCT ptl2.localizationId ORDER BY ptl2.localizationId) as locBId,
			GROUP_CONCAT(DISTINCT ptl2.sourceDb ORDER BY ptl2.sourceDb) as locBSourceDb,
			GROUP_CONCAT(DISTINCT ptl2.pubmedId ORDER BY ptl2.pubmedId) as locBPubmedId
		FROM Interaction i
        LEFT JOIN InteractionToSystemType itst ON i.id=itst.interactionId
        LEFT JOIN SystemType ist ON itst.interactionId=ist.id
		LEFT JOIN ConfidenceScore cs ON i.id=cs.interactionId
		LEFT JOIN Protein p1 ON p1.id=i.actorAId
		LEFT JOIN ProteinToLocalization ptl1 ON p1.id=ptl1.proteinId
		LEFT JOIN Protein p2 ON p2.id=i.actorBId
		LEFT JOIN ProteinToLocalization ptl2 ON p2.id=ptl2.proteinId
		WHERE (ptl1.localizationId IN(".join(',', $loc_id_pool).") OR ptl2.localizationId IN(".join(',', $loc_id_pool)."))";
		$sql_i .= ($species_id==-1 ? '' : " AND (p1.specieId=? AND p2.specieId=?)");
		$sql_i .= " GROUP BY i.id";
		$this->demo_mode ? $sql_i .= " LIMIT 1000" : '';
		
		if (!$r = $DB->executeQuery($sql_i, array($species_id, $species_id)))
			throw new \ErrorException('buildFullInteractions query failed!');

		$fp = fopen($this->downloads_dir.$filename, "w");

		// OUTPUT IN CSV
		// file header
		fwrite($fp, "Interactor A\tInteractor B\tConfidence Score\tExpSysType\tSourceDB\tPubmed\tMajor Loc. A\tMinor Loc. A\tLoc. A Source DB\tLoc. A Pubmed\tMajor Loc. B\tMinor Loc. B\tLoc. B Source DB\tLoc. B Pubmed\n");
		// file content
		while ($i = $r->fetch(\PDO::FETCH_OBJ))
		{
			// localizations for protein A
			if (!empty($i->locAId)) {
				$locAIds = explode(',', $i->locAId);
				foreach ($locAIds as $lid) {
					$tmp_majorLocAs[$lid] = (empty($lid) ? 'N/A' : $locs->getLargelocById($lid));
					$tmp_minorLocAs[$lid] = (empty($lid) ? 'N/A' : $locs->getHumanReadableLocalizationById($lid));
				}
				$majorLocAs = join(',', $tmp_majorLocAs);
				unset($tmp_majorLocAs); // reset or accumulates over the lines...
				$minorLocAs = join(',', $tmp_minorLocAs);
				unset($tmp_minorLocAs); // reset or accumulates over the lines...
			} else {
				$majorLocAs = 'N/A';
				$minorLocAs = 'N/A';
			}
			
			// localizations for protein B
			if (!empty($i->locBId)) {
				$locBIds = explode(',', $i->locBId);
				foreach ($locBIds as $lid) {
					$tmp_majorLocBs[$lid] = (empty($lid) ? 'N/A' : $locs->getLargelocById($lid));
					$tmp_minorLocBs[$lid] = (empty($lid) ? 'N/A' : $locs->getHumanReadableLocalizationById($lid));
				}
				$majorLocBs = join(',', $tmp_majorLocBs);
				unset($tmp_majorLocBs); // reset or accumulates over the lines...
				$minorLocBs = join(',', $tmp_minorLocBs);
				unset($tmp_minorLocBs); // reset or accumulates over the lines...
			} else {
				$majorLocBs = 'N/A';
				$minorLocBs = 'N/A';
			}
			
			fwrite($fp,
				 $i->actorA."\t"
				.$i->actorB."\t"
				.$i->confScore."\t"
				.$i->expSysType."\t"
				.$i->sourceDb."\t"
				.(!empty($i->pubmedId) ? $this->pubmed_link.$i->pubmedId : 'N/A')."\t"
				.$majorLocAs."\t"
				.$minorLocAs."\t"
				//.'sourceLocA'."\t"
				.$i->locASourceDb."\t"
				//.'pubmedLocA'."\t"
				.$this->pubmed_link.str_replace(',', ','.$this->pubmed_link, $i->locAPubmedId)."\t" // 123,456 -> http://.../123,http://.../456
				.$majorLocBs."\t"
				.$minorLocBs."\t"
				//.'sourceLocB'."\t"
				.$i->locBSourceDb."\t"
				//.'pubmedLocB'
				.$this->pubmed_link.str_replace(',', ','.$this->pubmed_link, $i->locBPubmedId)."\t"// 123,456 -> http://.../123,http://.../456
				."\n"
			);
		}
		
		fclose($fp);
		chmod($this->downloads_dir.$filename, 0777);
		
		if ($this->zipped_outputs) {
			if (!file_exists($this->downloads_dir.$filename))
				throw new \ErrorException('Source file not available to be zipped in buildFullInteractions()!');
			$zip = new ZipArchive();
			$zip->open($this->downloads_dir.$filename.'.zip',  ZipArchive::CREATE) OR die('ZIP ERROR!');
			$zip->addFile($this->downloads_dir.$filename);
			$zip->close();
			chmod($this->downloads_dir.$filename.'.zip', 0777);
		}
		
		$this->setTimeout(); // reset max execution time
		
		return true;
	}


    public function serveInteractionsAction($species) {
		$file = $this->downloads_dir.'comppi--interactions_'.$species['abbr'].'.csv';
        
		if (file_exists($file.'.zip')) {
			return $this->serveFile($file.'.zip');
		} elseif (file_exists($file)) {
            return $this->serveFile($file);
		} else {
			$this->buildInteractions($file, $species['id']);
			return $this->serveFile($file);
		}
    }
    
    
    private function buildInteractions($filename, $species_id)
    {
		//$this->setTimeout(240);
		$DB = $this->get('database_connection');

		// @TODO: species, locs!
        $sql = "SELECT
			i.id, i.sourceDb, i.pubmedId,
			GROUP_CONCAT(DISTINCT ist.name) AS expSysType,
			GROUP_CONCAT(DISTINCT cs.score) as confScore,
			p1.proteinName as actorA, p2.proteinName as actorB
		FROM Interaction i
        LEFT JOIN InteractionToSystemType itst ON i.id=itst.interactionId
        LEFT JOIN SystemType ist ON itst.interactionId=ist.id
		LEFT JOIN ConfidenceScore cs ON i.id=cs.interactionId
		LEFT JOIN Protein p1 ON p1.id=i.actorAId
		LEFT JOIN Protein p2 ON p2.id=i.actorBId";
		$sql .= ($species_id==-1 ? '' : " WHERE p1.specieId=? AND p2.specieId=?");
		$sql .= " GROUP BY i.id";
		$this->demo_mode ? $sql .= " LIMIT 1000" : "";
        
		if (!$r = $DB->executeQuery($sql, array($species_id, $species_id)))
			throw new \ErrorException('buildFullInteractions query failed!');

		$fp = fopen($this->downloads_dir.$filename, "w");
		
		// file header
		fwrite($fp, "Interactor A\tInteractor B\tConfidence Score\tExpSysType\tSourceDB\tPubmed\n");
		// file content
		while ($i = $r->fetch(\PDO::FETCH_OBJ))
		{
			fwrite($fp,
				 $i->actorA."\t"
				.$i->actorB."\t"
				.$i->confScore."\t"
				.$i->expSysType."\t"
				.$i->sourceDb."\t"
				.$this->pubmed_link.$i->pubmedId."\n"
			);
		}
		
		fclose($fp);
		chmod($this->downloads_dir.$filename, 0777);
		
		if ($this->zipped_outputs and class_exists('ZipArchive')) {
			if (!file_exists($this->downloads_dir.$filename))
				throw new \ErrorException('Source file not available to be zipped in buildFullInteractions()!');
			$zip = new ZipArchive();
			$zip->open($this->downloads_dir.$filename.'.zip', ZipArchive::CREATE) OR die('ZIP ERROR!');
			$zip->addFile($this->downloads_dir.$filename);
			$zip->close();
			chmod($this->downloads_dir.$filename.'.zip', 0777);
		}
		
		//$this->setTimeout(); // reset max execution time
		
		return true;
    }
    
    
    public function serveDetailedInteractionsAction($species) {
        $filename = 'comppi--interactions_with_details_'.$species['abbr'].'.csv';
		$output_filename = $filename.($this->zipped_outputs ? '.zip' : '');
        
        //if (!file_exists($this->downloads_dir.$output_filename))
            $this->buildDetailedInteractions($filename, $species['id']);
        
        return $this->serveFile($this->downloads_dir.$output_filename);
    }
    
    
    private function buildDetailedInteractions($filename, $species_id)
    {
		$this->setTimeout(240);
		ini_set('memory_limit', '512M');
		$DB = $this->get('database_connection');
//phpinfo();
//die();
		// @TODO: species, locs!
        $sql = "SELECT
			i.sourceDb, i.pubmedId, cs.score as ConfScore,
			ist.name AS expSysType,
			p1.proteinName as actorA, ptl1.localizationId as locAId, ptl1.sourceDb as locASourceDb, ptl1.pubmedId as locAPubmedId,
			p2.proteinName as actorB, ptl2.localizationId as locBId, ptl2.sourceDb as locBSourceDb, ptl2.pubmedId as locBPubmedId
		FROM Interaction i
        LEFT JOIN InteractionToSystemType itst ON i.id=itst.interactionId
        LEFT JOIN SystemType ist ON itst.interactionId=ist.id
		LEFT JOIN ConfidenceScore cs ON i.id=cs.interactionId
		LEFT JOIN Protein p1 ON p1.id=i.actorAId
		LEFT JOIN ProteinToLocalization ptl1 ON p1.id=ptl1.proteinId
		LEFT JOIN Protein p2 ON p2.id=i.actorBId
		LEFT JOIN ProteinToLocalization ptl2 ON p2.id=ptl2.proteinId";
		$sql .= ($species_id==-1 ? '' : " WHERE p1.specieId=? AND p2.specieId=?");
		$this->demo_mode ? $sql .= " LIMIT 100" : "";
		
		// SELECT THE INTERACTIONS WITH THEIR DETAILS
		$sql_i = "SELECT
			i.id as iid, i.sourceDb, i.pubmedId,
			GROUP_CONCAT(cs.score ORDER BY cs.score) as confScore,
			GROUP_CONCAT(ist.name ORDER BY ist.name) AS expSysType,
			p1.id as p1id, p1.proteinName as actorA,
			p2.id as p2id, p2.proteinName as actorB
		FROM Interaction i
        LEFT JOIN InteractionToSystemType itst ON i.id=itst.interactionId
        LEFT JOIN SystemType ist ON itst.interactionId=ist.id
		LEFT JOIN ConfidenceScore cs ON i.id=cs.interactionId
		LEFT JOIN Protein p1 ON p1.id=i.actorAId
		LEFT JOIN Protein p2 ON p2.id=i.actorBId";
		$sql_i .= ($species_id==-1 ? '' : " WHERE p1.specieId=? AND p2.specieId=?");
		$sql_i .= " GROUP BY i.id";
		$this->demo_mode ? $sql_i .= " LIMIT 1000" : "";
        
		if (!$r_i = $DB->executeQuery($sql_i, array($species_id, $species_id)))
			throw new \ErrorException('buildDetailedInteractions interaction query failed!');

		// CREATE THE SKELETON AND FILL WITH INTERACTION DETAILS
		//$protein_id_pool = array();
		while ($i = $r_i->fetch(\PDO::FETCH_OBJ))
		{	
			//die(var_dump($i));
			$content[] = array(
				'p1id' => $i->p1id,
				'p2id' => $i->p2id,
				'actorA' => $i->actorA,
				'actorB' => $i->actorB,
				'confScore' => (!isset($i->confScore) ? 'N/A' : $i->confScore),
				'expSysType' => (!isset($i->expSysType) ? 'N/A' : $i->expSysType),
				'sourceDb' => (empty($i->sourceDb) ? 'N/A' : $i->sourceDb),
				'pubmed_link' => (empty($i->pubmedId) ? 'N/A' : $this->pubmed_link.$i->pubmedId)
			);
			
			$protein_id_pool[$i->p1id] = $i->p1id;
			$protein_id_pool[$i->p2id] = $i->p2id;
		}
		$r_i->free();

		// SELECT LOCALIZATION DETAILS (FOR INTERACTIONS)
		$sql_locs = "SELECT
			proteinId, localizationId as locId, sourceDb as locSourceDb, pubmedId as locPubmedId
		FROM ProteinToLocalization ptl
		WHERE ptl.proteinId IN(".join(',', $protein_id_pool).")";
		if (!$r_locs = $DB->executeQuery($sql_locs))
			throw new \ErrorException('buildDetailedInteractions localization query failed!');
		
		// PARSE THE LOCALIZATION DETAILS
		$locs = $this->get('comppi.build.localizationTranslator');
		while ($l = $r_locs->fetch(\PDO::FETCH_OBJ))
		{
			$loc_data[$l->proteinId]['majorLoc'][] = (empty($l->locId) ? 'N/A' : ucfirst($locs->getLargelocById($l->locId)));
			$loc_data[$l->proteinId]['minorLoc'][] = (empty($l->locId) ? 'N/A' : ucfirst($locs->getHumanReadableLocalizationById($l->locId)));
			$loc_data[$l->proteinId]['sourceLoc'][] = (empty($l->locSourceDb) ? 'N/A' : $l->locSourceDb);
			$loc_data[$l->proteinId]['pubmedLoc'][] = (empty($l->locPubmedId) ? 'N/A' : $this->pubmed_link.$l->locPubmedId);
		}
		$r_locs->free();

		// FILL IN THE INTERACTION SKELETON WITH LOCALIZATION DATA AND WRITE TO FILE
		$fp = fopen($this->downloads_dir.$filename, "w");
		// file header
		fwrite($fp, "Interactor A\tInteractor B\tConfidence Score\tExpSysType\tSourceDB\tPubmed\tMajor Loc. A\tMinor Loc. A\tLoc. A Source DB\tLoc. A Pubmed\tMajor Loc. B\tMinor Loc. B\tLoc. B Source DB\tLoc. B Pubmed\n");
		// file content
		foreach($content as $row) {
			$row['majorLocA'] = (isset($loc_data[$row['p1id']]['majorLoc']) ? join(',', array_unique($loc_data[$row['p1id']]['majorLoc'])) : 'N/A');
			$row['minorLocA'] = (isset($loc_data[$row['p1id']]['minorLoc']) ? join(',', array_unique($loc_data[$row['p1id']]['minorLoc'])) : 'N/A');
			$row['sourceLocA'] = (isset($loc_data[$row['p1id']]['sourceLoc']) ? join(',', array_unique($loc_data[$row['p1id']]['sourceLoc'])) : 'N/A');
			$row['pubmedLocA'] = (isset($loc_data[$row['p1id']]['pubmedLoc']) ? join(',', array_unique($loc_data[$row['p1id']]['pubmedLoc'])) : 'N/A');
			
			$row['majorLocB'] = (isset($loc_data[$row['p2id']]['majorLoc']) ? join(',', array_unique($loc_data[$row['p2id']]['majorLoc'])) : 'N/A');
			$row['minorLocB'] = (isset($loc_data[$row['p2id']]['minorLoc']) ? join(',', array_unique($loc_data[$row['p2id']]['minorLoc'])) : 'N/A');
			$row['sourceLocB'] = (isset($loc_data[$row['p2id']]['sourceLoc']) ? join(',', array_unique($loc_data[$row['p2id']]['sourceLoc'])) : 'N/A');
			$row['pubmedLocB'] = (isset($loc_data[$row['p2id']]['pubmedLoc']) ? join(',', array_unique($loc_data[$row['p2id']]['pubmedLoc'])) : 'N/A');

			fwrite($fp,
				 $row['actorA']."\t"
				.$row['actorB']."\t"
				.$row['confScore']."\t"
				.$row['expSysType']."\t"
				.$row['sourceDb']."\t"
				.$row['pubmed_link']."\t"
				.$row['majorLocA']."\t"
				.$row['minorLocA']."\t"
				.$row['sourceLocA']."\t"
				.$row['pubmedLocA']."\t"
				.$row['majorLocB']."\t"
				.$row['minorLocB']."\t"
				.$row['sourceLocB']."\t"
				.$row['pubmedLocB']
				."\n"
			);
		}
		
		fclose($fp);
		chmod($this->downloads_dir.$filename, 0777);
		
		if ($this->zipped_outputs) {
			if (!file_exists($this->downloads_dir.$filename))
				throw new \ErrorException('Source file not available to be zipped in buildDetailedInteractions()!');
			$zip = new ZipArchive();
			$zip->open($this->downloads_dir.$filename.'.zip',  ZipArchive::CREATE) OR die('ZIP ERROR!');
			$zip->addFile($this->downloads_dir.$filename);
			$zip->close();
			chmod($this->downloads_dir.$filename.'.zip', 0777);
		}
		
		$this->setTimeout(); // reset max execution time
		
		return true;
    }
	
	
	public function serveProteinsAndLocsAction($species)
	{
		$file = $this->downloads_dir.'comppi--proteins_and_localizations_'.$species['abbr'].'.csv';
        
		if (file_exists($file.'.zip')) {
			return $this->serveFile($file.'.zip');
		} elseif (file_exists($file)) {
            return $this->serveFile($file);
		} else {
			$this->buildProteinsAndLocs(basename($file), $species['id']);
			return $this->serveFile($file);
		}
	}

	
	public function buildProteinsAndLocs($filename, $species_id)
	{
		$DB = $this->get('database_connection');
		
		// @TODO: species, locs!
        $sql = "SELECT
				p.id as pid, p.proteinName,
				GROUP_CONCAT(DISTINCT ptl.localizationId) as locId,
				GROUP_CONCAT(DISTINCT ptl.sourceDb ORDER BY ptl.sourceDb) as sourceDb,
				GROUP_CONCAT(DISTINCT ptl.pubmedId) as pubmedId,
				GROUP_CONCAT(DISTINCT st.name ORDER BY st.name) as expSysType
			FROM Protein p
			LEFT JOIN ProteinToLocalization ptl ON p.id=ptl.proteinId
			LEFT JOIN ProtLocToSystemType pltst ON ptl.id=pltst.protLocId
			LEFT JOIN SystemType st ON pltst.systemTypeId=st.id";
		$sql .= ($species_id==-1 ? '' : " WHERE p.specieId=?");
		$sql .= " GROUP BY p.id";
		$this->demo_mode ? $sql .= " LIMIT 1000" : "";
        
		if (!$r = $DB->executeQuery($sql, array($species_id)))
			throw new \ErrorException('buildProteinsAndLocs query failed!');

		$fp = fopen($this->downloads_dir.$filename, "w");
		$locs = $this->get('comppi.build.localizationTranslator');
		
		// file header
		fwrite($fp, "Protein\tMajor Loc\tMinor Loc\tExpSysType\tSourceDB\tPubmed\n");
		// file content
		while ($p = $r->fetch(\PDO::FETCH_OBJ))
		{
			// localizations
			if (!empty($p->locId)) {
				$locIds = explode(',', $p->locId);
				foreach ($locIds as $lid) {
					$tmp_majorLocs[$lid] = (empty($lid) ? 'N/A' : $locs->getLargelocById($lid));
					$tmp_minorLocs[$lid] = (empty($lid) ? 'N/A' : $locs->getHumanReadableLocalizationById($lid));
				}
				$majorLocs = join(',', $tmp_majorLocs);
				unset($tmp_majorLocs); // reset or accumulates over the lines...
				$minorLocs = join(',', $tmp_minorLocs);
				unset($tmp_minorLocs); // reset or accumulates over the lines...
			} else {
				$majorLocs = 'N/A';
				$minorLocs = 'N/A';
			}
			
			// output
			fwrite($fp,
				 $p->proteinName."\t"
				.$majorLocs."\t"
				.$minorLocs."\t"
				.$p->expSysType."\t"
				.$p->sourceDb."\t"
				.(!empty($p->pubmedId) ? $this->pubmed_link.str_replace(',', ','.$this->pubmed_link, $p->pubmedId) : 'N/A')."\n"
			);
		}
		
		fclose($fp);
		chmod($this->downloads_dir.$filename, 0777);
		
		// compress the output if we can
		if ($this->zipped_outputs and class_exists('ZipArchive')) {
			if (!file_exists($this->downloads_dir.$filename))
				throw new \ErrorException('Source file not available to be zipped in buildFullInteractions()!');
			$zip = new ZipArchive();
			$zip->open($this->downloads_dir.$filename.'.zip', ZipArchive::CREATE) OR die('ZIP ERROR!');
			$zip->addFile($this->downloads_dir.$filename);
			$zip->close();
			chmod($this->downloads_dir.$filename.'.zip', 0777);
		}
		
		return true;
	}
    
    
    private function setTimeout($seconds = null)
    {
        if (!isset($this->default_max_execution_time))
            $this->default_max_execution_time = ini_get('max_execution_time');
        
        if (is_null($seconds)) {
            ini_set('max_execution_time', $this->default_max_execution_time);
        } else {
            ini_set('max_execution_time', $seconds);
        }
    }
	
    
    private function serveFile($filepath)
    {
        if (file_exists($filepath))
        {
            $response = new Response();
            $this->createFileservingHeaders($response, basename($filepath));
            // stream_copy_to_stream() or file_get_contents() would be nicer, but that is a memory hog
            // Symfony2.1's StreamedResponse is not available in 2.0
            $response->sendHeaders();
            ob_clean();
            flush();
            readfile($filepath);
            exit();
        } else {
            throw new NotFoundException();
        }
    }
    
    private function createFileservingHeaders(Response $response, $filename)
    {
        session_cache_limiter('none');
		
		$response->headers->set('Content-Description', 'File Transfer');
		//$response->headers->set('Cache-Control', 'public');
		//$response->headers->set('Cache-Control', 'must-revalidate');
		$response->headers->set('Cache-Control', 'no-cache'); // this is the official
		$response->headers->set('Pragma', 'public');
		if ($this->zipped_outputs and pathinfo($filename, PATHINFO_EXTENSION)=='zip') {
			$response->headers->set('Content-Type', 'application/zip');
		} else {
			$response->headers->set('Content-Type', 'application/octet-stream');
		}
		$response->headers->set('Content-Transfer-Encoding', 'binary');
		$response->headers->set('Expires', '0');
		$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}
