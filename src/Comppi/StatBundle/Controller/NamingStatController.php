<?php

namespace Comppi\StatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class NamingStatController extends Controller
{
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

        /**
         * @var Comppi\BuildBundle\Service\SpecieProvider\SpecieProvider
         */
        $specieProvider = $this->container->get('comppi.build.specieProvider');

        foreach ($specieProvider->getDescriptors() as $specie) {
            $specieStats = $statistics->getNamingConventionStats($specie->id);

            $namingStats[$specie->abbreviation]['stat'] = $specieStats;
            $namingStats[$specie->abbreviation]['specieName'] = $specie->name;
            $namingStats[$specie->abbreviation]['totalProteinCount'] = 0;

            // add to total
            foreach ($specieStats as $stat) {
                if (!isset($totalCounts[$stat['namingConvention']])) {
                    $totalCounts[$stat['namingConvention']] = 0;
                }

                $totalCounts[$stat['namingConvention']] += $stat['proteinCount'];
                $namingStats['total']['totalProteinCount'] += $stat['proteinCount'];
                $namingStats[$specie->abbreviation]['totalProteinCount'] += $stat['proteinCount'];
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
     * @Route("/naming/proteins/{specieAbbr}/{convention}", name="stat_naming_proteins", requirements={"convention" = ".+"})
     * @Template()
     */
    public function proteinsByConventionAction($specieAbbr, $convention) {
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

        $connection = $this->getDoctrine()->getConnection();
        $selProteins = $connection->executeQuery(
            "SELECT proteinName FROM Protein" .
            " WHERE specieId = ? AND proteinNamingConvention = ?",
            array($specieId, $convention)
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
        /**
         * @var $statistics Comppi\StatBundle\Service\Statistics\Statistics
         */
        $statistics = $this->get('comppi.stat.statistics');

        /**
         * @var Comppi\BuildBundle\Service\SpecieProvider\SpecieProvider
         */
        $specieProvider = $this->container->get('comppi.build.specieProvider');

        $mapStats = array();

        foreach ($specieProvider->getDescriptors() as $specie) {
            $mapStats[$specie->name] = $statistics->getMapStats($specie->id);
        }

        return array(
            'mapStats' => $mapStats
        );
    }

    /**
     * @Route("/naming/conventions", name="stat_naming_conventions")
     * @Template()
     */
    public function conventionsAction() {
        return array();
    }
}