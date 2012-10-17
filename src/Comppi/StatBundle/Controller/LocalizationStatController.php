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

    /**
     * @Route("/localization/majorloc", name="stat_localization_majorloc")
     * @Template()
     */
    public function majorlocAction() {
        $majorlocGo = array (
            'GO:0043226',
    		'GO:0005739',
    		'GO:0005634',
    		'GO:0005576',
    		'secretory_pathway',
    		'GO:0016020'
        );

        $translator = $this->get('comppi.build.localizationTranslator');
        $majorLocs = array();
        foreach ($majorlocGo as $go) {
            $id = $translator->getIdByLocalization($go);
            $loc = array(
                'go' => $go,
                'id' => $id,
                'humanReadable' => $translator->getHumanReadableLocalizationById($id)
            );

            $majorLocs[] = $loc;
        }

        usort($majorLocs, array($this, 'sortByIdCallback'));

        return array (
            'majorLocs' => $majorLocs
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
            'jsonTree' => $jsonTree
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