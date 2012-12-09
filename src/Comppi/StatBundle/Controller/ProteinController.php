<?php

namespace Comppi\StatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class ProteinController extends Controller
{
    /**
     * @Route("/protein/{specieAbbr}/{id}", requirements={"id" = "\d+"}, name="stat_protein_protein")
     * @Template()
     */
    public function proteinAction($specieAbbr, $id)
    {
        /**
         * @var Comppi\BuildBundle\Service\SpecieProvider\SpecieProvider
         */
        $specieProvider = $this->container->get('comppi.build.specieProvider');

        try {
            $specie = $specieProvider->getSpecieByAbbreviation($specieAbbr);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException('Invalid species specified');
        }

        $pservice = $this->get('comppi.stat.protein');

        $protein = $pservice->get($specie->id, $id);

        if ($protein === false) {
            throw $this->createNotFoundException('Requested protein does not exist');
        }

        $synonyms = $pservice->getSynonyms($id);
        $localizations = $pservice->getLocalizations($id);

        if (is_array($localizations)) {
            $localizationTranslator = $this->get('comppi.build.localizationTranslator');

            foreach ($localizations as &$localization) {
                $localization['localizationName'] = $localizationTranslator->
                    getLocalizationById($localization['id']);
            }
        }

        $interactions = $pservice->getInteractions($id);

        if (is_array($interactions)) {
            foreach ($interactions as &$interaction) {
                $interaction['actor'] = $pservice->get($specie->id, $interaction['actorId']);
            }
        }

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
}