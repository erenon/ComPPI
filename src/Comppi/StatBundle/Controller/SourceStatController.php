<?php

namespace Comppi\StatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class SourceStatController extends Controller
{
    protected $species = array(
        'hs' => 'Homo Sapiens',
        'dm' => 'Drosophila Melanogaster',
        'ce' => 'Caernohabditis Elegans',
        'sc' => 'Saccaromicies Cervisae'
    );

    /**
     * @Route("/source", name="stat_source_index")
     * @Template()
     */
    public function indexAction()
    {
        return array(
            'species' => $this->species
        );
    }

    /**
     * @Route("/source/{specie}", name="stat_source_specie")
     * @Template()
     */
    public function sourceBySpecieAction($specie)
    {
        if (isset($this->species[$specie])) {
            $specieName = $this->species[$specie];
        } else {
            throw $this->createNotFoundException('Invalid specie specified');
        }

        /**
         * @var $statistics Comppi\StatBundle\Service\Statistics\Statistics
         */
        $statistics = $this->get('comppi.stat.statistics');
        $interactionSourceStat = $statistics->getInteractionSourceStats($specie);
        $locaizationSourceStat = $statistics->getLocalizationSourceStats($specie);
        $sourceProteinCounts = $statistics->getSourceProteinCounts($specie);

        $totalInteractionProteinCount = 0;
        $totalInteractionCount = 0;

        $totalLocalizationProteinCount = 0;
        $totalLocalizationCount = 0;

        foreach ($interactionSourceStat as $key => $stat) {
            $interactionSourceStat[$key]['proteinCount'] =
                $sourceProteinCounts[$stat['database']];

            $totalInteractionProteinCount += $sourceProteinCounts[$stat['database']];
            $totalInteractionCount += $stat['interactionCount'];
        }

        foreach ($locaizationSourceStat as $key => $stat) {
            $locaizationSourceStat[$key]['proteinCount'] =
                $sourceProteinCounts[$stat['database']];

            $totalLocalizationProteinCount += $sourceProteinCounts[$stat['database']];
            $totalLocalizationCount += $stat['localizationCount'];
        }

        return array(
        	'specieName' => $specieName,
            'interactions' => $interactionSourceStat,
            'interactionTotal' => array(
                'proteinCount' => $totalInteractionProteinCount,
                'interactionCount' => $totalInteractionCount
            ),
            'localizations' => $locaizationSourceStat,
            'localizationTotal' => array(
                'proteinCount' => $totalLocalizationProteinCount,
                'localizationCount' => $totalLocalizationCount
            ),
        	'species' => $this->species // subnav menu entries
        );
    }
}
