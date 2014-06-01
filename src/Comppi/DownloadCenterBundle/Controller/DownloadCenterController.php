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
	private $locs = array(
		'0' => 'cytoplasm',
		'1' => 'mitochondrion',
		'2' => 'nucleus',
		'3' => 'extracellular',
		'4' => 'secretory-pathway',
		'5' => 'membrane',
		'all' => 'all'
	);
	private $specii = array(
		'0' => 'H. sapiens',
		'1' => 'D. melanogaster',
		'2' => 'C. elegans',
		'3' => 'S. cerevisiae',
		'all' => 'All'
	);
    
	/*
	Main method of the DownloadCenter.
	
	Served files are in the $this->downloads_dir, file names are determined in the comppi/bin/export_downloads.py file.

	*/
    public function downloadCenterAction()
    {
		//comppi--proteins_localizations-sp_{}-loc_{}.txt
		//comppi--interactions-sp_{}-loc_{}.txt
		
		//$sp = $this->speciesProvider = $this->get('comppi.build.specieProvider');
		
        $T = array();
		
		$request = $this->getRequest();
		if ($request->getMethod() == 'POST')
		{
			$dltype = '';
			switch ($_POST['fDlSet'])
			{
				case 'int':
				case 'comp':
					$dltype = 'interactions';
					break;
				case 'protnloc':
					$dltype = 'proteins_localizations';
					break;
				// default: file will not be found
			}
			
			$species = 'all';
			if (!empty($_POST["fDlSpec"]) and isset($this->specii[$_POST["fDlSpec"]]))
			{
				$species = $_POST["fDlSpec"];
			}
			
			$loc = 'all';
			if (!empty($_POST["fDlMLoc"]) and isset($this->locs[$_POST["fDlMLoc"]]))
			{
				$loc = $this->locs[$_POST["fDlMLoc"]];
			}
			
			$dl_path = $this->downloads_dir."comppi--$dltype-sp_$species-loc_$loc.txt";
			$this->serveFile($dl_path);
		}
		
		# all releases
		$T['releases_ls'] = array();
		$d = dir($this->releases_dir);
        while (false !== ($entry = $d->read())) {
            if ($entry!='.' && $entry!='..' && $entry!=$this->current_db_sql && $entry!='.htaccess' && $entry!='stable')
            {
                $entry = array(
                    'file' => $entry,
                    'size' => number_format((filesize($this->releases_dir.$entry)/1048576), 2, '.', ' '),
                    'mtime' => date("Y-m-d. H:i:s", filemtime($this->releases_dir.$entry))
                );
                $T['releases_ls'][$entry['mtime']] = $entry;
            }
        }
        $d->close();
		
		$curr_release_mtime = date("Y-m-d. H:i:s", filemtime($this->releases_dir.$this->current_db_sql));
		$curr_release_entry = array(
			'file' => $this->current_db_sql,
			'size' => number_format((filesize($this->releases_dir.$this->current_db_sql)/1048576), 2, '.', ' '), // show in MB
			'mtime' => $curr_release_mtime
		);
		# current release entry ensures that curr. release is displayed exactly once
		$T['releases_ls'][$curr_release_mtime] = $curr_release_entry;
		krsort($T['releases_ls']);
		
        return $this->render('DownloadCenterBundle:DownloadCenter:downloadcenter.html.twig', $T);
    }

    
    public function serveReleaseAction($file)
    {
        return $this->serveFile($this->releases_dir.$file); // $this->releases_dir prevents cross-site serving!
    }

	
//	public function serveAllDataAction()
//	{	
//		return $this->serveFile($this->releases_dir.$this->current_db_sql);
//	}
//	
//	
//	public function serveComparmentalizedDataAction($species)
//	{
//		// we define which compartments are needed... (keys are the same as to be found in $locs->getLargelocs(), see buildComparmentalizedData() method)
//		$compartments = array();
//		isset($_POST['fDlMLocCytoplasm']) ? $compartments['cytoplasm'] = true : '';
//		isset($_POST['fDlMLocMito']) ? $compartments['mitochondrion'] = true : '';
//		isset($_POST['fDlMLocNucleus']) ? $compartments['nucleus'] = true : '';
//		isset($_POST['fDlMLocEC']) ? $compartments['extracellular'] = true : '';
//		isset($_POST['fDlMLocSecr']) ? $compartments['secretory-pathway'] = true : '';
//		isset($_POST['fDlMLocPlasMembr']) ? $compartments['membrane'] = true : '';
//
//		if (empty($compartments)) {
//			$_SESSION['messages']['compartment_error'] = "Please select at least one compartment!";
//			return $this->forward('DownloadCenterBundle:DownloadCenter:currentReleaseGui');
//		}
//		
//		$file = $this->downloads_dir.'comppi--interactions_by_compartments--'.join('_', array_keys($compartments)).'--'.$species['abbr'].'.csv';
//        
//		if (file_exists($file.'.zip')) {
//			return $this->serveFile($file.'.zip');
//		} elseif (file_exists($file)) {
//            return $this->serveFile($file);
//		} else {
//			$this->buildComparmentalizedData(basename($file), $species['id'], $compartments);
//			return $this->serveFile($file);
//		}
//	}
//
//
//    public function serveInteractionsAction($species, $interaction_ids = array()) {
//		if (!empty($interaction_ids)) {
//			$file = $this->downloads_dir.'comppi--interactions_custom.csv';
//		} else {
//			$file = $this->downloads_dir.'comppi--interactions_'.$species['abbr'].'.csv';
//		}
//        
//		if (!empty($interaction_ids)) {
//			$this->buildInteractions($file, $species['id'], $interaction_ids);
//				return $this->serveFile($file);
//		} else {
//			if (file_exists($file.'.zip')) {
//				return $this->serveFile($file.'.zip');
//			} elseif (file_exists($file)) {
//				return $this->serveFile($file);
//			} else {
//				$this->buildInteractions($file, $species['id']);
//				return $this->serveFile($file);
//			}
//		}
//    }
//	
//	
//	public function serveProteinsAndLocsAction($species)
//	{
//		$file = $this->downloads_dir.'comppi--proteins_and_localizations_'.$species['abbr'].'.csv';
//        
//		if (file_exists($file.'.zip')) {
//			return $this->serveFile($file.'.zip');
//		} elseif (file_exists($file)) {
//            return $this->serveFile($file);
//		} else {
//			$this->buildProteinsAndLocs(basename($file), $species['id']);
//			return $this->serveFile($file);
//		}
//	}
	
    
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
            echo "The requested file '$filepath' is not found.";
			exit();
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
