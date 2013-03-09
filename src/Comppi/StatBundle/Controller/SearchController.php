<?php

namespace Comppi\StatBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller
{
    /**
     * @Route("/search", name="stat_search_index")
     * @Template()
     */
    public function indexAction()
    {
        $searchForm = $this->getSearchForm();

        return array(
            'searchForm' => $searchForm->createView(),
        );
    }

    /**
     * @Route("/search/search", name="stat_search_search")
     * @Template()
     */
    public function searchAction(Request $request)
    {
        if ($request->getMethod() == 'GET') {
            $searchForm = $this->getSearchForm();
            $searchForm->bindRequest($request);

            if ($searchForm->isValid()) {
                $data = $searchForm->getData();
                $searchTerm = trim($data['searchTerm']);

                if (empty($searchTerm)) {
                    return $this->redirect(
                        $this->generateUrl('stat_search_index'),
                        303 // See Other
                    );
                }

                $search = $this->get('comppi.stat.search');

                // replace * with %
                if ($searchTerm[0] === '*') {
                    $searchTerm[0] = '%';
                }

                if ($searchTerm[strlen($searchTerm) - 1] === '*') {
                    $searchTerm[strlen($searchTerm) - 1] = '%';
                }

                $results = $search->searchByName($searchTerm);

                // check overflow
                $overflow = $results['_overflow'];
                unset($results['_overflow']);

                return array(
                    'searchForm' => $searchForm->createView(),
                    'results' => $results,
                    'overflow' => $overflow
                );
            }
        }

        return array(
            'searchForm' => $searchForm->createView(),
        	'results' => array()
        );
    }

    /**
     * @Route("/search/autocomplete/{query}", name="stat_search_autocomplete")
     */
    public function autocompleteAction($query) {
        $search = $this->get('comppi.stat.search');
        $names = $search->getNamesContaining($query);

        $response = array();
        $response['names'] = array();
        foreach ($names as $name) {
            $response['names'][] = $name['name'];
        }

        return new Response(json_encode($response));
    }

    private function getSearchForm() {
        $searchForm = $this->createFormBuilder(
            null,
            array(
                'csrf_protection' => false
            )
        )
            ->add('searchTerm', 'text')
            ->getForm();

        return $searchForm;
    }
}