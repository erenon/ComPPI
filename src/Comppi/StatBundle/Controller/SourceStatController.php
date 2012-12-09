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
        /**
         * @var Comppi\BuildBundle\Service\SpecieProvider\SpecieProvider
         */
        $specieProvider = $this->container->get('comppi.build.specieProvider');

        return array(
            'species' => $specieProvider->getDescriptors()
        );
    }

    /**
     * @Route("/source/{specieAbbr}", name="stat_source_specie")
     * @Template()
     */
    public function sourceBySpecieAction($specieAbbr)
    {
        /**
         * @var Comppi\BuildBundle\Service\SpecieProvider\SpecieProvider
         */
        $specieProvider = $this->container->get('comppi.build.specieProvider');

        try {
            $specie = $specieProvider->getSpecieByAbbreviation($specieAbbr);
            $specieId = $specie->id;
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException('Invalid species specified');
        }

        /**
         * @var $statistics Comppi\StatBundle\Service\Statistics\Statistics
         */
        $statistics = $this->get('comppi.stat.statistics');

        $interactionSourceStat = $statistics->getInteractionSourceStats($specieId);
        $locaizationSourceStat = $statistics->getLocalizationSourceStats($specieId);
        $sourceProteinCounts = $statistics->getSourceProteinCounts($specieId);

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
        	'specieName' => $specie->name,
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
        	'species' => $specieProvider->getDescriptors() // subnav menu entries
        );
    }
}
