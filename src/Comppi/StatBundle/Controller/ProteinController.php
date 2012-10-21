<?php

namespace Comppi\StatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class ProteinController extends Controller
{
    /**
     * @Route("/protein/{specie}/{id}", requirements={"specie" = "hs|dm|ce|sc", "id" = "\d+"}, name="stat_protein_protein")
     * @Template()
     */
    public function proteinAction($specie, $id)
    {
        $pservice = $this->get('comppi.stat.protein');

        $protein = $pservice->get($specie, $id);

        if ($protein === false) {
            throw $this->createNotFoundException('Requested protein does not exist');
        }

        $synonyms = $pservice->getSynonyms($specie, $id);
        $localizations = $pservice->getLocalizations($specie, $id);

        if (is_array($localizations)) {
            $localizationTranslator = $this->get('comppi.build.localizationTranslator');

            foreach ($localizations as &$localization) {
                $localization['localizationName'] = $localizationTranslator->
                    getLocalizationById($localization['id']);
            }
        }

        $interactions = $pservice->getInteractions($specie, $id);

        if (is_array($interactions)) {
            foreach ($interactions as &$interaction) {
                $interaction['actor'] = $pservice->get($specie, $interaction['actorId']);
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