<?php

namespace Comppi\DescriptionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DescriptionController extends Controller
{
    
    public function AboutAction()
    {
        return $this->render('ComppiDescriptionBundle:Description:about.html.twig');
    }

    public function HelpAction()
    {
        return $this->render('ComppiDescriptionBundle:Description:help.html.twig');
    }

    public function ImpressumAction()
    {
        return $this->render('ComppiDescriptionBundle:Description:impressum.html.twig');
    }
}
