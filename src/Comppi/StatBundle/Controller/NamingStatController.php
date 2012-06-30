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
     */
    public function indexAction()  {
        return $this->forward('StatBundle:NamingStat:distribution');
    }

    /**
     * @Route("/naming/distribution", name="stat_naming_distribution")
     * @Template()
     */
    public function distributionAction()  {
        $namingStats = array();

        // init total
        $namingStats['total'] = array();
        $namingStats['total']['specieName'] = 'TOTAL';
        $namingStats['total']['totalProteinCount'] = 0;

        $totalCounts = array();

        /**
         * @var $statistics Comppi\StatBundle\Service\Statistics\Statistics
         */
        $statistics = $this->get('comppi.stat.statistics');
        foreach ($this->species as $specie => $specieName) {
            $specieStats = $statistics->getNamingConventionStats($specie);

            $namingStats[$specie]['stat'] = $specieStats;
            $namingStats[$specie]['specieName'] = $specieName;
            $namingStats[$specie]['totalProteinCount'] = 0;

            // add to total
            foreach ($specieStats as $stat) {
                if (!isset($totalCounts[$stat['namingConvention']])) {
                    $totalCounts[$stat['namingConvention']] = 0;
                }

                $totalCounts[$stat['namingConvention']] += $stat['proteinCount'];
                $namingStats['total']['totalProteinCount'] += $stat['proteinCount'];
                $namingStats[$specie]['totalProteinCount'] += $stat['proteinCount'];
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

    /**
     * @Route("/naming/proteins/{specie}/{convention}", name="stat_naming_proteins", requirements={"convention" = ".+"})
     * @Template()
     */
    public function proteinsByConventionAction($specie, $convention) {
        if (!array_key_exists($specie, $this->species)) {
            // invalid specie abbr.
            throw $this->createNotFoundException('Invalid specie specified');
        }

        $connection = $this->getDoctrine()->getConnection();
        $table = 'Protein' . ucfirst($specie);
        $selProteins = $connection->executeQuery(
            "SELECT proteinName FROM " . $table . " WHERE proteinNamingConvention = ?",
            array($convention)
        );

        $proteins = $selProteins->fetchAll(\PDO::FETCH_COLUMN);

        return array (
            'proteins' => $proteins
        );
    }

    /**
     * @Route("/naming/mapping", name="stat_naming_mapping")
     * @Template()
     */
    public function mappingAction() {
        return array();
    }

    /**
     * @Route("/naming/conventions", name="stat_naming_conventions")
     * @Template()
     */
    public function conventionsAction() {
        return array();
    }
}