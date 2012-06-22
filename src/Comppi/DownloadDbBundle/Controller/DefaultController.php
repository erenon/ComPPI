<?php

namespace Comppi\DownloadDbBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DefaultController extends Controller
{
    
    public function indexAction($name)
    {
        return $this->render('ComppiDownloadDbBundle:Default:index.html.twig', array('name' => $name));
    }
}
