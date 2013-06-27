<?php

namespace Comppi\DownloadCenterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class DownloadCenterController extends Controller
{
    private $releases_dir = './dbreleases/'; // trailing slash is important!
    private $downloads_dir = './downloads/';
    private $current_db_sql = 'comppi.sql';
	private $pubmed_link = 'http://www.ncbi.nlm.nih.gov/pubmed/';
	private $zipped_outputs = false; // UNTESTED WITH TRUE!
	private $species = array();
    
    public function currentReleaseGuiAction()
    {
		$sp = $this->get('comppi.build.specieProvider');
        $T = array();
		
		$path = $this->releases_dir.$this->current_db_sql;
		$T['dset_all_size'] = number_format((filesize($path)/1048576), 2, '.', ' '); // get it in MB
        $T['dset_all_mtime'] = date("Y-m-d. H:i:s", filemtime($path));
		
		
		/*$T['need_hs'] = ($_POST['fDlSpec']=='hs' ? 1 : 0);
		$T['need_dm'] = ($_POST['fDlSpec']=='dm' ? 1 : 0);
		$T['need_ce'] = ($_POST['fDlSpec']=='ce' ? 1 : 0);
		$T['need_sc'] = ($_POST['fDlSpec']=='sc' ? 1 : 0);
		
		$T['need_cytoplasm'] = (isset($_POST['fDlSpecSc']) ? 1 : 0);
		$T['need_mito'] = (isset($_POST['fDlSpecSc']) ? 1 : 0);
		$T['need_nucleus'] = (isset($_POST['fDlSpecSc']) ? 1 : 0);
		$T['need_ec'] = (isset($_POST['fDlSpecSc']) ? 1 : 0);
		$T['need_secr'] = (isset($_POST['fDlSpecSc']) ? 1 : 0);
		$T['need_plasmembr'] = (isset($_POST['fDlSpecSc']) ? 1 : 0);*/
		
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
		if ($request->getMethod() == 'POST') {
			if (!isset($_POST['fDlSpec']) or $_POST['fDlSpec']=='all') {
				$species = 'all';
			} else {
				$species = $sp->getSpecieByAbbreviation($_POST['fDlSpec']);
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
		$compartments = array();
		isset($_POST['fDlMLocCytoplasm']) ? $compartments['cytoplasm'] = true : '';
		isset($_POST['fDlMLocMito']) ? $compartments['mito'] = true : '';
		isset($_POST['fDlMLocNucleus']) ? $compartments['nucleus'] = true : '';
		isset($_POST['fDlMLocEC']) ? $compartments['ec'] = true : '';
		isset($_POST['fDlMLocSecr']) ? $compartments['secr'] = true : '';
		isset($_POST['fDlMLocPlasMembr']) ? $compartments['plasmembr'] = true : '';
		
		$locs = $this->get('comppi.build.localizationTranslator');
		die(var_dump($locs->getLargelocs()));
		
		
		$filename = 'comppi--interactions_'.$species.'.csv';
		$output_filename = $filename.($this->zipped_outputs ? '.zip' : '');
        
        if (!file_exists($this->downloads_dir.$output_filename))
            $this->buildInteractions($filename, $species);
        
        return $this->serveFile($this->downloads_dir.$output_filename);
	}
	
	
	private function buildComparmentalizedData()
	{
		$locs = $this->get('comppi.build.localizationTranslator');
	}


    public function serveInteractionsAction($species) {
        $filename = 'comppi--interactions_'.$species.'.csv';
		$output_filename = $filename.($this->zipped_outputs ? '.zip' : '');
        
        if (!file_exists($this->downloads_dir.$output_filename))
            $this->buildInteractions($filename, $species);
        
        return $this->serveFile($this->downloads_dir.$output_filename);
    }
    
    
    private function buildInteractions($filename, $species)
    {
		//$this->setTimeout(240);
		$DB = $this->get('database_connection');

		// @TODO: species, locs!
        $sql = "SELECT
			i.sourceDb, i.pubmedId,
			ist.name AS expSysType,
			p1.proteinName as actorA, p2.proteinName as actorB
		FROM Interaction i
        LEFT JOIN InteractionToSystemType itst ON i.id=itst.interactionId
        LEFT JOIN SystemType ist ON itst.interactionId=ist.id
		LEFT JOIN Protein p1 ON p1.id=i.actorAId
		LEFT JOIN Protein p2 ON p2.id=i.actorBId";
		$sql .= ($species=='all' ? '' : " WHERE p1.specieId=? AND p2.specieId=?");
        
		if (!$r = $DB->executeQuery($sql, array($species, $species)))
			throw new \ErrorException('buildFullInteractions query failed!');

		$fp = fopen($this->downloads_dir.$filename, "w");
		
		// file header
		fwrite($fp, "Interactor A\tInteractor B\tExpSysType\tSourceDB\tPubmed\n");
		// file content
		while ($i = $r->fetch(\PDO::FETCH_OBJ))
		{
			fwrite($fp,
				 $i->actorA."\t"
				.$i->actorB."\t"
				.$i->expSysType."\t"
				.$i->sourceDb."\t"
				.$this->pubmed_link.$i->pubmedId."\n"
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
		
		//$this->setTimeout(); // reset max execution time
		
		return true;
    }
    
    
    public function serveDetailedInteractionsAction($species) {
        $filename = 'comppi--interactions_with_details_'.$species.'.csv';
		$output_filename = $filename.($this->zipped_outputs ? '.zip' : '');
        
        if (!file_exists($this->downloads_dir.$output_filename))
            $this->buildDetailedInteractions($filename, $species);
        
        return $this->serveFile($this->downloads_dir.$output_filename);
    }
    
    
    private function buildDetailedInteractions($filename, $species)
    {
		$this->setTimeout(240);
		$DB = $this->get('database_connection');
		

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
		$sql .= ($species=='all' ? '' : " WHERE p1.specieId=? AND p2.specieId=?");
        
		if (!$r = $DB->executeQuery($sql, array($species, $species)))
			throw new \ErrorException('buildFullInteractions query failed!');

		$fp = fopen($this->downloads_dir.$filename, "w");
		$locs = $this->get('comppi.build.localizationTranslator');
		
		// file header
		fwrite($fp, "Interactor A\tInteractor B\tConfidence Score\tExpSysType\tSourceDB\tPubmed\tMajor Loc. A\tMinor Loc. A\tLoc. A Source DB\tLoc. A Pubmed\tMajor Loc. B\tMinor Loc. B\tLoc. B Source DB\tLoc. B Pubmed\n");
		// file content
		while ($i = $r->fetch(\PDO::FETCH_OBJ))
		{
			fwrite($fp,
				 $i->actorA."\t"
				.$i->actorB."\t"
				.$i->ConfScore."\t"
				.$i->expSysType."\t"
				.$i->sourceDb."\t"
				.$this->pubmed_link.$i->pubmedId."\t"
				.(empty($p->locAId) ? 'N/A' : ucfirst($locs->getLargelocById($p->locAId)))."\t"
				.(empty($p->locAId) ? 'N/A' : ucfirst($locs->getHumanReadableLocalizationById($p->locAId)))."\t"
				.$i->locASourceDb."\t"
				.$this->pubmed_link.$i->locAPubmedId."\t"
				.(empty($p->locBId) ? 'N/A' : ucfirst($locs->getLargelocById($p->locBId)))."\t"
				.(empty($p->locBId) ? 'N/A' : ucfirst($locs->getHumanReadableLocalizationById($p->locBId)))."\t"
				.$i->locBSourceDb."\t"
				.$this->pubmed_link.$i->locBPubmedId."\n"
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
		$filename = 'comppi--proteins_and_localizations_'.$species.'.csv';
		$output_filename = $filename.($this->zipped_outputs ? '.zip' : '');
        
        if (!file_exists($this->downloads_dir.$output_filename))
            $this->buildProteinsAndLocs($filename, $species);
        
        return $this->serveFile($this->downloads_dir.$output_filename);
	}

	
	public function buildProteinsAndLocs($filename, $species)
	{
		$DB = $this->get('database_connection');
		
		// @TODO: species, locs!
        $sql = "SELECT p.id as pid, p.proteinName, ptl.localizationId as locId, ptl.sourceDb, ptl.pubmedId,st.name as expSysType
			FROM Protein p
			LEFT JOIN ProteinToLocalization ptl ON p.id=ptl.proteinId
			LEFT JOIN ProtLocToSystemType pltst ON ptl.id=pltst.protLocId
			LEFT JOIN SystemType st ON pltst.systemTypeId=st.id";
		$sql .= ($species=='all' ? '' : " WHERE p.specieId=?");
        
		if (!$r = $DB->executeQuery($sql, array($species)))
			throw new \ErrorException('buildFullInteractions query failed!');

		$fp = fopen($this->downloads_dir.$filename, "w");
		$locs = $this->get('comppi.build.localizationTranslator');
		
		// file header
		fwrite($fp, "Protein\tMajor Loc\tMinor Loc\tExpSysType\tSourceDB\tPubmed\n");
		// file content
		while ($p = $r->fetch(\PDO::FETCH_OBJ))
		{
			fwrite($fp,
				 $p->proteinName."\t"
				.(empty($p->locId) ? 'N/A' : ucfirst($locs->getLargelocById($p->locId)))."\t"
				.(empty($p->locId) ? 'N/A' : ucfirst($locs->getHumanReadableLocalizationById($p->locId)))."\t"
				.$p->expSysType."\t"
				.$p->sourceDb."\t"
				.$this->pubmed_link.$p->pubmedId."\n"
			);
		}
		
		fclose($fp);
		chmod($this->downloads_dir.$filename, 0777);
		
		return true;
	}
    
	/*
	
	$sql = "SELECT
			i.id as iid,
			ist.name,
			p1.proteinName as actorA, p2.proteinName as actorB,
			ptl1.id as protLocAId, ptl1.localizationId as locAId,
			ptl2.id as protLocBId, ptl2.localizationId as locBId,
			st1.id as sysTypeAId, st1.confidenceType as confTypeA, st2.id as sysTypeBId, st1.confidenceType as confTypeB
		FROM Interaction i
        LEFT JOIN InteractionToSystemType itst ON i.id=itst.interactionId
        LEFT JOIN SystemType ist ON itst.interactionId=ist.id
		LEFT JOIN Protein p1 ON p1.id=i.actorAId
		LEFT JOIN ProteinToLocalization ptl1 ON p1.id=ptl1.proteinId
		LEFT JOIN ProtLocToSystemType ptst1 ON ptl1.id=ptst1.protLocId
		LEFT JOIN SystemType st1 ON ptst1.systemTypeId=st1.id
		LEFT JOIN Protein p2 ON p2.id=i.actorBId
		LEFT JOIN ProteinToLocalization ptl2 ON p2.id=ptl2.proteinId
		LEFT JOIN ProtLocToSystemType ptst2 ON ptl2.id=ptst2.protLocId
		LEFT JOIN SystemType st2 ON ptst2.systemTypeId=st2.id";
	*/
    
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


class Compartment
{
	public $primaryId;
	public $secondaryId;
	
	public function __construct($go_term)
	{
		
	}
}
