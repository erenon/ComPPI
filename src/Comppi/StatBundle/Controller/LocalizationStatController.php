<?php

namespace Comppi\StatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class LocalizationStatController extends Controller
{
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

    	/**
         * @var Comppi\BuildBundle\Service\SpecieProvider\SpecieProvider
         */
        $specieProvider = $this->container->get('comppi.build.specieProvider');

        foreach ($specieProvider->getDescriptors() as $specie) {
            $specieStats = $statistics->getLocalizationStats($specie->id);

            // transform localizationId to localization name
            foreach ($specieStats as $key => $stat) {
                try {
                    $specieStats[$key]['localizationName'] = $translator->getHumanReadableLocalizationById(
                        $stat['localizationId']
                    );
                } catch (\InvalidArgumentException $e) {
                    $specieStats[$key]['localizationName'] = 'N/A';
                }

                try {
                    $specieStats[$key]['goAccession'] = $translator->getLocalizationById(
                        $stat['localizationId']
                    );
                } catch (\InvalidArgumentException $e) {
                    $specieStats[$key]['goAccession'] = 'N/A';
                }
            }

            $locStats[$specie->id]['stat'] = $specieStats;
            $locStats[$specie->id]['specieName'] = $specie->name;
        }

        return array (
            'specieLocalizationStats' => $locStats,
            '_action' => 'index'
        );
    }

    /**
     * @Route("/localization/majorloc", name="stat_localization_majorloc")
     * @Template()
     */
    public function majorlocAction() {
        $translator = $this->get('comppi.build.localizationTranslator');
        $largelocs = $translator->getLargelocs();
        $largelocs['N/A'] = $translator->getIdsWithoutLargeloc();

        foreach ($largelocs as &$largeloc) {
            foreach ($largeloc as $key => $id) {
                $child = array();
                $child['localizationId'] = $id;
                try {
                    $child['localizationName'] =
                        $translator->getHumanReadableLocalizationById($id);
                } catch (\InvalidArgumentException $e) {
                    $child['localizationName'] = 'N/A';
                }

                try {
                    $child['goAccession'] =
                        $translator->getLocalizationById($id);
                } catch (\InvalidArgumentException $e) {
                    $child['goAccession'] = 'N/A';
                }

                $largeloc[$key] = $child;
            }
        }

        return array(
            'largelocs' => $largelocs,
            '_action' => 'majorloc'
        );
    }

    /**
     * @Route("/localization/visualization", name="stat_localization_visualization")
     * @Template()
     */
    public function visualizationAction() {
        $translator = $this->get('comppi.build.localizationTranslator');

        // get tree
        $tree = $translator->getLocalizationTree();

        // create common root
        $root = array (
            'id' => 0,
            'sid' => 0, // will be unsetted
            'name' => 'Cellular component',
            'humanReadable' => 'Cellular component',
            'data' => '',
            'children' => array()
        );

        // bind toplevels to the common root
        foreach ($tree as $originalRoot) {
            $root['children'][] = $originalRoot;
        }

        // move humanReadable to data.humanReadable
        // shorten names
        // unset unnecessary fields
        $root = $this->transformLoctree($root);

        // create json
        $jsonTree = json_encode($root);

        // escape apostrophes
        $jsonTree = addslashes($jsonTree);

        return array (
            'jsonTree' => $jsonTree,
            '_action' => 'visualization'
        );
    }

    private function sortByIdCallback($a, $b) {
        if ($a['id'] == $b['id']) {
            return 0;
        }

        return ($a['id'] < $b['id']) ? -1 : 1;
    }

    private function transformLoctree($root) {
        $root['data']['humanReadable'] = $root['humanReadable'];

        $root['name'] = $root['humanReadable'];
        if (strlen($root['name']) > 12) {
            $root['name'] = substr($root['name'], 0, 9) . '...';
        }

        unset($root['humanReadable']);
        unset($root['sid']);

        if (isset($root['children']) && !empty($root['children'])) {
            foreach ($root['children'] as $key => $child) {
                $root['children'][$key] = $this->transformLoctree($child);
            }
        } else {
            $root['children'] = array();
        }

        return $root;
    }
}