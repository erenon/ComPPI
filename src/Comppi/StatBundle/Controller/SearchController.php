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
        $searchForm = $this->createFormBuilder(null)
            ->add('searchTerm', 'text')
            ->getForm();

        $search = $this->get('comppi.stat.search');

        $examples = $search->getExamples();
        $exampleNames = array();

        foreach ($examples as $example) {
            $exampleNames[] = $example['name'];
        }

        uasort($exampleNames, 'strcmp');

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
        $searchForm = $this->createFormBuilder(null)
            ->add('searchTerm', 'text')
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $searchForm->bindRequest($request);

            if ($searchForm->isValid()) {
                $data = $searchForm->getData();
                $searchTerm = trim($data['searchTerm']);

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
}