<?php

namespace Comppi\ProteinSearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class ProteinSearchController extends Controller
{
    public function proteinSearchAction($protein_name = '')
    {
        $T = array(
            'ls' => array()
        );

		$name = '143';
        $species_requested = array(
            'Hs' => 1,
            'Dm' => 1,
            'Ce' => 1,
            'Sc' => 1
        );

		// Low-level Doctrine DBAL commands with custom query building to have better control
		// @TODO: convert to Doctrine query builder ( conn->createQueryBuilder() )?
		$DB = $this->get('database_connection');
		$locs = $this->get('comppi.build.localizationTranslator');

		foreach($species_requested as $sp => $specie_needed) {
			if ( $specie_needed ) {
				$sql = "SELECT p1.proteinName AS protA, p2.proteinName AS protB, i.actorAId, i.actorBId, ptl1.localizationId AS locAId, ptl1.pubmedId AS locASrc, ptl2.localizationId AS locBId, ptl1.pubmedId AS locBSrc FROM Interaction$sp i LEFT JOIN Protein$sp p1 ON i.actorAId=p1.id LEFT JOIN Protein$sp p2 ON i.actorBId=p2.id LEFT JOIN ProteinToLocalization$sp ptl1 ON actorAId=ptl1.proteinId LEFT JOIN ProteinToLocalization$sp ptl2 ON actorBId=ptl2.proteinId WHERE p1.proteinName LIKE '%$name%' OR p2.proteinName LIKE '%$name%'";
				$results = $DB->query( $sql );
				// @TODO: exception handling here
				while ( $p = $results->fetch() ) {
					$T['ls'][] = array(
						'protA' => $p['protA'],
						'locA' => (empty($p['locAId']) ? 'N/A' : $locs->getHumanReadableLocalizationById($p['locAId'])),
						'locASrcUrl' => $this->linkToPubmed($p['locASrc']),
						'protB' => $p['protB'],
						'locB' => (empty($p['locBId']) ? 'N/A' : $locs->getHumanReadableLocalizationById($p['locBId'])),
						'locBSrcUrl' => $this->linkToPubmed($p['locBSrc'])
					);
				}
            }
        }
//var_dump($T['ls']);
		$T['sql'] = '';
		
		//var_dump( $Hs_ids );
		
		return $this->render('ComppiProteinSearchBundle:ProteinSearch:index.html.twig', $T);
	}
	
	private function linkToPubmed($pubmed_uid)
	{
		return 'http://www.ncbi.nlm.nih.gov/sites/entrez?from_uid='.$pubmed_uid;
	}
}
