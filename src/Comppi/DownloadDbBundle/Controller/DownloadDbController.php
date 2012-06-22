<?php

namespace Comppi\DownloadDbBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DownloadDbController extends Controller
{
    
    public function downloadAction()
    {
        $T = array();
		
		return $this->render('ComppiDownloadDbBundle:DownloadDb:download.html.twig', $T);
    }
}
