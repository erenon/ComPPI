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
	private $specii_dl_opts = array(
		'0' => 'hsapiens',
		'1' => 'dmelanogaster',
		'2' => 'celegans',
		'3' => 'scerevisiae',
		'all' => 'all'
	);

	/*
	Main method of the DownloadCenter.

	Served files are in the $this->downloads_dir, file names are determined in the comppi/bin/export_downloads.py file.

	*/
    public function downloadCenterAction()
    {
		//comppi--proteins_locs-tax_{}-loc_{}.txt
		//comppi--compartments--tax_{}-loc_{}.txt
		//comppi--interactions--tax_{}-loc_{}.txt

		//$sp = $this->speciesProvider = $this->get('comppi.build.specieProvider');

        $T = array();

		$request = $this->getRequest();
		if ($request->getMethod() == 'POST')
		{
			$dltype = '';
			switch ($_POST['fDlSet'])
			{
				case 'int':
					$dltype = 'interactions';
					break;
				case 'comp':
					$dltype = 'compartments';
					break;
				case 'protnloc':
					$dltype = 'proteins_locs';
					break;
				// default: file will not be found
			}

			$species = 'all';
			if (isset($_POST["fDlSpec"]) and isset($this->specii_dl_opts[$_POST["fDlSpec"]]))
			{
				$species = $this->specii_dl_opts[$_POST["fDlSpec"]];
			}

			$loc = 'all';
			if (isset($_POST["fDlMLoc"]) and isset($this->locs[$_POST["fDlMLoc"]]))
			{
				$loc = $this->locs[$_POST["fDlMLoc"]];
			}

			$dl_path = $this->downloads_dir
				."comppi--".$dltype."--tax_".$species."_loc_".$loc.".txt.gz";
			$this->serveFile($dl_path);
		}

		# all releases
		$T['releases_ls'] = array();
		if (is_dir($this->releases_dir)) {
			if ($d = dir($this->releases_dir)) {
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
			}

		}


        return $this->render('DownloadCenterBundle:DownloadCenter:downloadcenter.html.twig', $T);
    }


    public function serveReleaseAction($file)
    {
        return $this->serveFile($this->releases_dir.$file); // $this->releases_dir prevents cross-site serving!
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
