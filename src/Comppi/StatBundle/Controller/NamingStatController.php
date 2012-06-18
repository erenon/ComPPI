<?php

namespace Comppi\StatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class NamingStatController extends Controller
{
    protected $species = array(
        'hs' => 'Homo Sapiens',
        'dm' => 'Drosophila Melanogaster',
        'ce' => 'Caernohabditis Elegans',
        'sc' => 'Saccaromicies Cervisae'
    );

   /**
     * @Route("/naming", name="stat_naming_index")
     * @Template()
     */
    public function indexAction()  {
        $namingStats = array();

        // init total
        $namingStats['total'] = array();
        $namingStats['total']['specieName'] = 'TOTAL';

        $totalCounts = array();

        /**
         * @var $statistics Comppi\StatBundle\Service\Statistics\Statistics
         */
        $statistics = $this->get('comppi.stat.statistics');
        foreach ($this->species as $specie => $specieName) {
            $specieStats = $statistics->getNamingConventionStats($specie);

            $namingStats[$specie]['stat'] = $specieStats;
            $namingStats[$specie]['specieName'] = $specieName;

            // add to total
            foreach ($specieStats as $stat) {
                if (!isset($totalCounts[$stat['namingConvention']])) {
                    $totalCounts[$stat['namingConvention']] = 0;
                }

                $totalCounts[$stat['namingConvention']] += $stat['proteinCount'];
            }
        }

        // aggregate total
        foreach ($totalCounts as $convention => $totalCount) {
            $namingStats['total']['stat'][] = array (
                'namingConvention' => $convention,
                'proteinCount' => $totalCount
            );
        }

        uasort($namingStats['total']['stat'], array($this, 'sortByProteinCountCallback'));

        return array (
            'namingStats' => $namingStats
        );
    }

    protected function sortByProteinCountCallback($a, $b) {
        if ($a['proteinCount'] == $b['proteinCount']) {
            return 0;
        }

        return ($a['proteinCount'] < $b['proteinCount']) ? 1 : -1;
    }
}