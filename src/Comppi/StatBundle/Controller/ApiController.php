<?php

namespace Comppi\StatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends Controller
{
    /**
     * @Route("/api/search/{searchTerm}/", name="stat_api_search")
     */
    public function searchAction($searchTerm)
    {
        $search = $this->get('comppi.stat.search');
        
        // replace * with %
        if ($searchTerm[0] === '*') {
            $searchTerm[0] = '%';
        }

        if ($searchTerm[strlen($searchTerm) - 1] === '*') {
            $searchTerm[strlen($searchTerm) - 1] = '%';
        }
                
        $results = $search->searchByName($searchTerm);

        unset($results['_overflow']);

        foreach ($results as &$result) {
            unset($result['specieId']);
            $result['specie'] = $result['specie']->name;
        }

        $response = array();
        $response['results'] = $results;

        return new Response(json_encode($response));
    }

    /**
     * @Route("/api/protein/{id}", requirements={"id" = "\d+"}, name="stat_api_protein")
     */
    public function proteinAction($id)
    {
        $pservice = $this->get('comppi.stat.protein');

        $protein = $pservice->get($id);

        if ($protein === false) {
            throw $this->createNotFoundException('Requested protein does not exist');
        }

        $protein['synonyms'] = $pservice->getSynonyms($id);
        $localizations = $pservice->getLocalizations($id);

        if (is_array($localizations)) {
            $localizationTranslator = $this->get('comppi.build.localizationTranslator');

            foreach ($localizations as &$localization) {
                $localization['localizationName'] = $localizationTranslator->
                    getHumanReadableLocalizationById($localization['localizationId']);
            }

            $protein['localizations'] = $localizations;
        } else {
            $protein['localizations'] = array();
        }

        $protein['interactions'] = $pservice->getInteractions($id);
        $response['protein'] = $protein;

        return new Response(json_encode($response));
    }
}
