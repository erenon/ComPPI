<?php

namespace Comppi\DownloadDbBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class DownloadDbController extends Controller
{
    // Property of conditions, shared across methods
	private $species_requested = array(
		'Hs' => 0,
		'Dm' => 0,
		'Ce' => 0,
		'Sc' => 0
	);
	private $species_ncbi_taxids = array(
		'Hs' => '9606', // H. sapiens
		'Dm' => '7227', // D. melanogaster
		'Ce' => '6239', // C. elegans
		'Sc' => '4932' // S. cerevisiae
	);
	//private $selected_dataset = 0; // serveFullDb: 0, serveInteractionsByLocalizations: 1, serveInteractions: 2, serveLocalizations: 3
    
	public function downloadAction()
    {
		/*
		cytoplasm:			GO:0043226 = 3134
		mitochondrion: 		+GO:0005739 = 863
		nucleus:			GO:0005634 = 1005
		extracellular:		GO:0005576 = 2582
		secretory pathway:	secretory pathway = 1
		plasma membrane:	+GO:0016020 = 2796
		
		$locs = $this->get('comppi.build.localizationTranslator');
		die( var_dump( $locs->getIdByLocalization('secretory_pathway') ) );
		*/
		
		$T = array(
			//'need_hs' => $request->request->get('fDownloadSpecHs')
			'need_hs' => ( empty($this->species_requested['Hs']) ? 0 : 1 ),
			'need_dm' => ( empty($this->species_requested['Dm']) ? 0 : 1 ),
			'need_ce' => ( empty($this->species_requested['Ce']) ? 0 : 1 ),
			'need_sc' => ( empty($this->species_requested['Sc']) ? 0 : 1 ),
			//'dataset' => ( !empty($this->selected_dataset) ? $this->selected_dataset : 0 ),
		);
		$T['locs'] = $this->buildLocTree();
		
		return $this->render('ComppiDownloadDbBundle:DownloadDb:download.html.twig', $T);
    }
	
	public function serveAction()
	{
		// @TODO: add  a server-side check if all species are  0 (currently it is checked only on  cliend side)
		// @TODO: add proper check of input data
	    $request = $this->getRequest();
		if ($request->getMethod() == 'POST') {
			$this->species_requested['Hs'] = intval($request->request->get('fDownloadSpecHs'));
			$this->species_requested['Dm'] = intval($request->request->get('fDownloadSpecDm'));
			$this->species_requested['Ce'] = intval($request->request->get('fDownloadSpecCe'));
			$this->species_requested['Sc'] = intval($request->request->get('fDownloadSpecSc'));
			//$this->selected_dataset = intval($request->request->get('fDownloadDataset'));

			// @TODO: store e-mail
			
			$is_intbyloc = $request->request->get('fDownloadIntByLoc');
			$is_int = $request->request->get('fDownloadInts');
			$is_loc = $request->request->get('fDownloadLocs');
			if ( !empty($is_intbyloc) ) {
				$response = $this->serveInteractionsByLocalizations();
			} elseif ( !empty($is_int) ) {
				$response = $this->serveInteractions();
			} elseif ( !empty($is_loc) ) {
				$response = $this->serveLocalizations();
			} else {
				$response = $this->serveCustomDownload();
				
			}

			return $response; // $response->send() is called automatically
		} else {
			// forwarding to downloadAction
			$response = $this->forward('ComppiDownloadDbBundle:DownloadDb:serve', array());
			return $response;
		}
	}

	private function serveInteractionsByLocalizations()
	{
		return new Response('Not implemented yet!');
	}

	private function serveInteractions()
	{
		$response = $this->createResponse();
		$this->setResponseHeaders($response, 'interactions');
		$DB = $this->get('database_connection');
		$content = '"protein A","naming convention A","protein B","naming convention B","NCBI TaxID"'."\n";
		
		foreach($this->species_requested as $sp => $specie_needed) {
			if ( $specie_needed ) {
				$sql = "SELECT p1.proteinName AS protA, p1.proteinNamingConvention AS convA, p2.proteinName AS protB, p2.proteinNamingConvention AS convB FROM Interaction$sp i LEFT JOIN Protein$sp p1 ON i.actorAId=p1.id LEFT JOIN Protein$sp p2 ON i.actorBId=p2.id";
				$results = $DB->query( $sql );
				// @TODO: exception handling here
				while ( $r = $results->fetch() ) {
					$content .= '"'.$r['protA'].'","'.$r['convA'].'","'.$r['protB'].'","'.$r['convB'].'","'.$this->species_ncbi_taxids[$sp].'"'."\n";
				}
            }
        }
		
		$response->setContent($content);
		return $response;
	}
	
	private function serveLocalizations()
	{
		$response = $this->createResponse();
		$this->setResponseHeaders($response, 'localizations');
		$DB = $this->get('database_connection');
		$locs = $this->get('comppi.build.localizationTranslator');
		$content = '"Protein","Localization","Data Type"'."\n";
		
		foreach($this->species_requested as $sp => $specie_needed) {
			if ( $specie_needed ) {
				$sql = "SELECT proteinName, localizationId, experimentalSystemType FROM Protein$sp p, ProteinToLocalization$sp ptl WHERE p.id=ptl.proteinId";
				$results = $DB->query( $sql );
				// @TODO: exception handling here
				while ( $r = $results->fetch() ) {
					$content .= '"'.$r['proteinName'].'","'.$locs->getHumanReadableLocalizationById($r['localizationId']).'","'.$r['experimentalSystemType'].'"'."\n";
				}
            }
        }

		$response->setContent($content);
		return $response;
	}
	
	private function serveCustomDownload()
	{
		$request = $this->getRequest();
		$request_loc_ids = $request->request->get('fDownloadLocFine');
		$locs = $this->get('comppi.build.localizationTranslator');
		$loc_conds = array();
		foreach($request_loc_ids as $id) {
			$loc_go = $locs->getLocalizationById(intval($id));
			$sec_id = $locs->getSecondaryIdByLocalization($loc_go);
			$loc_conds[] = '(('.intval($id)." < ptl1.localizationId AND ptl1.localizationId < $sec_id) OR (".intval($id)." < ptl2.localizationId AND ptl2.localizationId < $sec_id))";
		}
		
		//die(var_dump( $request->request->get('fDownloadLocFine') ));
		
		$response = $this->createResponse();
		$this->setResponseHeaders($response, 'custom');
		$DB = $this->get('database_connection');
		$content = '"protein A","localization A","naming convention A","protein B","localization B","naming convention B","NCBI TaxID"'."\n";

		foreach($this->species_requested as $sp => $specie_needed) {
			if ( $specie_needed ) {
				$sql = "SELECT p1.proteinName AS protA, p1.proteinNamingConvention AS convA, ptl1.localizationId AS locAId, p2.proteinName AS protB, p2.proteinNamingConvention AS convB, ptl2.localizationId AS locBId FROM Interaction$sp i LEFT JOIN Protein$sp p1 ON i.actorAId=p1.id LEFT JOIN Protein$sp p2 ON i.actorBId=p2.id LEFT JOIN ProteinToLocalization$sp ptl1 ON i.actorAId=ptl1.proteinId LEFT JOIN ProteinToLocalization$sp ptl2 ON i.actorBId=ptl2.proteinId WHERE ".join(" OR ", $loc_conds);
				die($sql);
				$results = $DB->query( $sql );
				// @TODO: exception handling here
				while ( $r = $results->fetch() ) {
					$content .= '"'.$r['protA'].'","'.$locs->getHumanReadableLocalizationById($r['locAId']).'","'.$r['convA'].'","'.$r['protB'].'","'.$locs->getHumanReadableLocalizationById($r['locBId']).'","'.$r['convB'].'","'.$this->species_ncbi_taxids[$sp].'"'."\n";
				}
            }
        }

		$response->setContent($content);
		return $response;
	}
	
	private function createResponse()
    {
        $response = new Response();
        return $response;
    }

    private function setResponseHeaders(Response $response, $filename_fragment)
    {
		session_cache_limiter('none');
		
		$response->headers->set('Content-Description', 'File Transfer');
		//$response->headers->set('Cache-Control', 'public');
		//$response->headers->set('Cache-Control', 'must-revalidate');
		$response->headers->set('Cache-Control', 'no-cache'); // this is the official
		$response->headers->set('Pragma', 'public');
		$response->headers->set('Content-Type', 'application/octet-stream');
		$response->headers->set('Content-Transfer-Encoding', 'binary');
		$response->headers->set('Expires', '0');
		$response->headers->set('Content-Disposition', 'attachment; filename="comppi--'.$filename_fragment.'--'.date("Y-m-d").'.csv"');
		// filesize: we don't know it, we are streaming...
    }
	
	private function buildLocTree()
	{
		$locs = $this->get('comppi.build.localizationTranslator');
		$loctreedata = $locs->getLocalizationTree();
		$loclist = array();
		
		foreach($loctreedata as $ltd_id => $node) {
			$this->recursiveLocTreeHelper($node, $loclist);
		}
		
		//die( var_dump( $loclist ) );
		return $loclist;
	}
	
	private function recursiveLocTreeHelper($node, &$loclist, $depth = 0)
	{
		$loclist[] = array('id' => $node['id'], 'label' => str_repeat('.', $depth).ucfirst($node['humanReadable'].' ('.$node['name'].')'));
		$depth++;
		if (isset($node['children'])) {
			foreach ($node['children'] as $child) {
				$this->recursiveLocTreeHelper($child, $loclist, $depth);
			}
		}
	}
}