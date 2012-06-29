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
	private $selected_dataset = 0; // serveFullDb: 0, serveInteractionsByLocalizations: 1, serveInteractions: 2, serveLocalizations: 3
    
	public function downloadAction()
    {
		$this->buildLocTree();
		
		$T = array(
			//'need_hs' => $request->request->get('fDownloadSpecHs')
			'need_hs' => ( $this->species_requested['Hs'] ? 0 : 1 ),
			'need_dm' => ( $this->species_requested['Dm'] ? 0 : 1 ),
			'need_ce' => ( $this->species_requested['Ce'] ? 0 : 1 ),
			'need_sc' => ( $this->species_requested['Sc'] ? 0 : 1 ),
			'dataset' => ( !empty($this->selected_dataset) ? $this->selected_dataset : 0 ),
		);
		
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
			$this->selected_dataset = intval($request->request->get('fDownloadDataset'));

			// @TODO: store e-mail
			
			switch ($this->selected_dataset) {
				case 1:		$response = $this->serveInteractionsByLocalizations();	break;
				case 2:		$response = $this->serveInteractions();					break;
				case 3:		$response = $this->serveLocalizations();				break;
				default:	// @TODO: throw an excpt.
					$response = $this->createResponse();
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
		$this->setResponseHeaders($response);
		$DB = $this->get('database_connection');
		
		foreach($this->species_requested as $sp => $specie_needed) {
			if ( $specie_needed ) {
				$sql = "SELECT p1.proteinName AS protA, p1.proteinNamingConvention AS convA, p2.proteinName AS protB, p2.proteinNamingConvention AS convB FROM Interaction$sp i LEFT JOIN Protein$sp p1 ON i.actorAId=p1.id LEFT JOIN Protein$sp p2 ON i.actorBId=p2.id";
				$results = $DB->query( $sql );
				// @TODO: exception handling here
				echo '"protein A","naming convention A","protein B","naming convention B","NCBI TaxID"'."\n";
				while ( $r = $results->fetch() ) {
					echo '"'.$r['protA'].'","'.$r['convA'].'","'.$r['protB'].'","'.$r['convB'].'","'.$species_ncbi_taxids[$sp].'"'."\n";
				}
            }
        }

		return $response;
	}
	
	private function serveLocalizations()
	{
		$response = $this->createResponse();
		$this->setResponseHeaders($response);
		$DB = $this->get('database_connection');
		$locs = $this->get('comppi.build.localizationTranslator');
		
		foreach($this->species_requested as $sp => $specie_needed) {
			if ( $specie_needed ) {
				$sql = "SELECT proteinName, localizationId, experimentalSystemType FROM Protein$sp p, ProteinToLocalization$sp ptl WHERE p.id=ptl.proteinId";
				$results = $DB->query( $sql );
				// @TODO: exception handling here
				echo '"protein","localization","type"'."\n";
				while ( $r = $results->fetch() ) {
					echo '"'.$r['proteinName'].'","'.$locs->getHumanReadableLocalizationById($r['localizationId']).'","'.$r['experimentalSystemType'].'"'."\n";
				}
            }
        }

		return $response;
	}
	
	private function createResponse()
    {
        $response = new Response();
        return $response;
    }

    private function setResponseHeaders(Response $response)
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
		$response->headers->set('Content-Disposition', 'attachment; filename="comppi-'.date("Y-m-d").'.csv"');
		// filesize: we don't know it, we are streaming...
    }
	
	private function buildLocTree()
	{
		$locs = $this->get('comppi.build.localizationTranslator');
		$loctree = $locs->getLocalizationTree();
		
		die( var_dump( $loctree ) );
	}
}