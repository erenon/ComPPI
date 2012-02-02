<?php

namespace Comppi\DownloadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Comppi\DownloadBundle\Service\Localizations;

class DownloadController extends Controller
{
    /**
     * @Route("/list")
     * @Template
     */
    public function listAction() {
        /** @var $locService \Comppi\DownloadBundle\Service\Localizations */
        $locService = $this->get("comppi.download.localizations");

        $locals = $locService->getLocalizations();
        
        return array(
            'locals' => $locals
        );
    }
    
    /**
     * @Route("/download/{local}")
     * @Template
     */
    public function downloadAction($local) {
        /** @var $locService \Comppi\DownloadBundle\Service\Localizations */
        $locService = $this->get("comppi.download.localizations");

        $interactions = $locService->getInteractions($local);
        
        return array(
            'localization' => $local,
            'interactions' => $interactions
        );        
    }
    
}