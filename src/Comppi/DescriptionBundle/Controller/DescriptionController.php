<?php

namespace Comppi\DescriptionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DescriptionController extends Controller
{
    
    public function AboutAction()
    {
        return $this->render('ComppiDescriptionBundle:Description:about.html.twig');
    }


    public function HelpAction($subpage)
    {
        $t = '';
        switch($subpage)
        {
            case 'cite_us':
                $t = 'ComppiDescriptionBundle:Description:cite_us.html.twig';
                break;
            case 'downloads':
                $t = 'ComppiDescriptionBundle:Description:download.html.twig';
                break;
            case 'input_databases':
                $t = 'ComppiDescriptionBundle:Description:input_databases.html.twig';
                break;
            case 'introduction':
                $t = 'ComppiDescriptionBundle:Description:introduction.html.twig';
                break;
            case 'naming_conventions':
                $t = 'ComppiDescriptionBundle:Description:naming_conventions.html.twig';
                break;
            case 'other_tools_and_sources':
                $t = 'ComppiDescriptionBundle:Description:other_tools_and_sources.html.twig';
                break;
            case 'output_formats':
                $t = 'ComppiDescriptionBundle:Description:output_formats.html.twig';
                break;
            case 'protein_search':
                $t = 'ComppiDescriptionBundle:Description:protein_search.html.twig';
                break;
            case 'scores':
                $t = 'ComppiDescriptionBundle:Description:scores.html.twig';
                break;
            case 'species':
                $t = 'ComppiDescriptionBundle:Description:species.html.twig';
                break;
            case 'subcell_locs':
                $t = 'ComppiDescriptionBundle:Description:subcell_locs.html.twig';
                break;
            case 'terms_of_use':
                $t = 'ComppiDescriptionBundle:Description:terms_of_use.html.twig';
                break;
            case 'tutorial':
                $t = 'ComppiDescriptionBundle:Description:tutorial.html.twig';
                break;
            default:
                $t = 'ComppiDescriptionBundle:Description:help.html.twig';
        }
        return $this->render($t);
    }


    public function ImpressumAction()
    {
        return $this->render('ComppiDescriptionBundle:Description:impressum.html.twig');
    }
}
