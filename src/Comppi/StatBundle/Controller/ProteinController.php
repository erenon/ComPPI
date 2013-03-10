<?php

namespace Comppi\StatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProteinController extends Controller
{
    /**
     * @Route("/protein/{id}", requirements={"id" = "\d+"}, name="stat_protein_protein")
     * @Template()
     */
    public function proteinAction($id)
    {
        $pservice = $this->get('comppi.stat.protein');
        $protein = $pservice->get($id);

        if ($protein === false) {
            throw $this->createNotFoundException('Requested protein does not exist');
        }

        // get species
        try {
            /**
             * @var Comppi\BuildBundle\Service\SpecieProvider\SpecieProvider
             */
            $specieProvider = $this->container->get('comppi.build.specieProvider');
            $specie = $specieProvider->getSpecieById($protein['specieId']);
        } catch (\InvalidArgumentException $e) {
            throw new Exception("Invalid specieId found in database", 500, $e);
        }

        $synonyms = $pservice->getSynonyms($id);
        $localizations = $pservice->getLocalizations($id);

        if (is_array($localizations)) {
            $localizationTranslator = $this->get('comppi.build.localizationTranslator');

            foreach ($localizations as &$localization) {
                $localization['localizationName'] = $localizationTranslator->
                    getLocalizationById($localization['localizationId']);
            }
        }

        $interactions = $pservice->getInteractions($id);

        return array(
            'main' => array(
                'specie' => $specie,
                'name' => $protein['name'],
                'namingConvention' => $protein['namingConvention']
            ),
            'synonyms' => $synonyms,
            'localizations' => $localizations,
            'interactions' => $interactions
        );
    }

    /**
     * @Route("/proteindetails/interaction/{id}", requirements={"id" = "\d+"}, name="stat_protein_intdetails")
     * @Template()
     */
    public function interactionDetailsAction($id) {
        $pservice = $this->get('comppi.stat.protein');
        $interactionDetails = $pservice->getInteractionDetails($id);

        $scoreService = $this->get('comppi.build.confidenceScore');

        foreach ($interactionDetails['confidenceScores'] as &$score) {
            $score['name'] = $scoreService->getCalculatorName($score['calculatorId']);
        }

        $request = $this->getRequest();

        if ($request->isXmlHttpRequest()) {
            $response = new Response(json_encode($interactionDetails));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            return $interactionDetails;
        }
    }

    /**
     * @Route("/proteindetails/localization/{id}", requirements={"id" = "\d+"}, name="stat_protein_locdetails")
     * @Template()
     */
    public function localizationDetailsAction($id) {
        $pservice = $this->get('comppi.stat.protein');
        $localizationDetails = $pservice->getLocalizationDetails($id);

        $request = $this->getRequest();

        if ($request->isXmlHttpRequest()) {
            $response = new Response(json_encode($localizationDetails));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            return $localizationDetails;
        }
    }
}