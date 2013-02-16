<?php

namespace Comppi\StatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DownloadController extends Controller
{
    /**
     * @Route("/download", name="stat_download_index")
     * @Template()
     */
    public function indexAction()  {
        return array();
    }
}