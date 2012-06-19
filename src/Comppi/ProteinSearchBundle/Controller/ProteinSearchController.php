<?php

namespace Comppi\ProteinSearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class ProteinSearchController extends Controller
{

    public function IndexAction($protein_name = '')
    {
        $T = array(
            'ls' => array()
        );

        return $this->render('ComppiProteinSearchBundle:ProteinSearch:index.html.twig', $T);
    }
}
