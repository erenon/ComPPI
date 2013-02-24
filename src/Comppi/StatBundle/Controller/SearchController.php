<?php

namespace Comppi\StatBundle\Controller;

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
        $search = $this->get('comppi.stat.search');

        $examples = $search->getExamples();
        $exampleNames = array();

        foreach ($examples as $example) {
            $exampleNames[] = $example['name'];
        }

        uasort($exampleNames, 'strcmp');

        $searchForm = $this->getSearchForm();

        return array(
            'searchForm' => $searchForm->createView(),
            'examples' => $exampleNames
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
                $results = $search->searchByName($searchTerm);

                return array(
                    'searchForm' => $searchForm->createView(),
                    'results' => $results
                );
            }
        }

        return array(
            'searchForm' => $searchForm->createView(),
        	'results' => array()
        );
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