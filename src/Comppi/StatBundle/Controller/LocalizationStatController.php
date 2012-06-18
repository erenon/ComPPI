<?php

namespace Comppi\StatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class LocalizationStatController extends Controller
{
    protected $species = array(
        'hs' => 'Homo Sapiens',
        'dm' => 'Drosophila Melanogaster',
        'ce' => 'Caernohabditis Elegans',
        'sc' => 'Saccaromicies Cervisae'
    );

    /**
     * @Route("/localization", name="stat_localization_index")
     * @Template()
     */
    public function indexAction()
    {
        $locStats = array();

        /**
         * @var $translator Comppi\BuildBundle\Service\LocalizationTranslator
         */
        $translator = $this->get('comppi.build.localizationTranslator');

        /**
         * @var $statistics Comppi\StatBundle\Service\Statistics\Statistics
         */
        $statistics = $this->get('comppi.stat.statistics');
        foreach ($this->species as $specie => $specieName) {
            $specieStats = $statistics->getLocalizationStats($specie);

            // transform localizationId to localization name
            foreach ($specieStats as $key => $stat) {
                try {
                    $specieStats[$key]['localizationName'] = $translator->getLocalizationById(
                        $stat['localizationId']
                    );
                } catch (\InvalidArgumentException $e) {
                    $specieStats[$key]['localizationName'] = 'N/A';
                }
            }

            $locStats[$specie]['stat'] = $specieStats;
            $locStats[$specie]['specieName'] = $specieName;
        }

        return array (
            'specieLocalizationStats' => $locStats
        );
    }
}