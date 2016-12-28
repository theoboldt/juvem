<?php

namespace AppBundle\Controller;

use AppBundle\Twig\GlobalCustomization;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LegalController extends Controller
{

    /**
     * @Route("/legal", name="legal")
     * @Route("/datenschutzerklaerung")
     * @Route("/datenschutz")
     */
    public function legalAction()
    {
        return $this->render('legal/privacy-page.html.twig');
    }

    /**
     * @Route("/conditions-of-travel", name="conditions_of_travel")
     * @Route("/reisebedingungen")
     */
    public function conditionsOfTravelAction()
    {
        $rootDir = $this->get('kernel')->getRootDir();
        if (GlobalCustomization::isCustomizationAvailable($rootDir, 'conditions-of-travel-content')) {
            return $this->render('legal/conditions-of-travel-page.html.twig');
        } else {
            return $this->redirectToRoute('imprint');
        }
    }

    /**
     * @Route("/imprint", name="imprint")
     * @Route("/impressum")
     */
    public function imprintAction()
    {
        return $this->render('legal/imprint-page.html.twig');
    }
}
