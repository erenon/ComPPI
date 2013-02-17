<?php

namespace Comppi\StatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class InteractionStatController extends Controller
{
    /**
     * @Route("/interaction", name="stat_interaction_index")
     */
    public function indexAction()  {
        return $this->forward('StatBundle:InteractionStat:distribution');
    }

    /**
     * @Route("/interaction/distribution", name="stat_interaction_distribution")
     * @Template()
     */
    public function distributionAction() {
        $species = $this->get('comppi.build.specieProvider')->getDescriptors();

        return array(
            'species' => $species
        );
    }
}