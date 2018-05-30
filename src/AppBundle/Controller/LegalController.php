<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Twig\GlobalCustomization;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class LegalController extends Controller
{

    /**
     * @Route("/legal", name="legal")
     * @Route("/datenschutzerklaerung")
     * @Route("/datenschutz")
     */
    public function legalAction()
    {
        $description   = 'Aufklärung über die Art, den Umfang und Zwecke der Erhebung und Verwendung personenbezogener Daten dieser Seite.';

        $response = new Response();
        $response->headers->add(['X-Robots-Tag' => ['noindex', 'noarchive']]);

        return $this->render('legal/privacy-page.html.twig', ['pageDescription' => $description], $response);
    }

    /**
     * @Route("/conditions-of-travel", name="conditions_of_travel")
     * @Route("/reisebedingungen")
     */
    public function conditionsOfTravelAction()
    {
        $customization = $this->get('app.twig_global_customization');
        $description   = 'Diese Bedingungen gelten bei den Veranstaltungen, die von ' . $customization->organizationName() . ' auf dieser Seite angeboten werden.';

        $rootDir = $this->get('kernel')->getRootDir();
        if (GlobalCustomization::isCustomizationAvailable($rootDir, 'conditions-of-travel-content')) {
            return $this->render('legal/conditions-of-travel-page.html.twig', ['pageDescription' => $description]);
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
        $description   = 'Hier finden Sie alle Angaben zu Verantwortlichkeiten und Informationen, wie Sie mit uns Kontakt auf nehmen können.';

        $response = new Response();
        $response->headers->add(['X-Robots-Tag' => ['noindex', 'noarchive']]);

        return $this->render('legal/imprint-page.html.twig', ['pageDescription' => $description], $response);
    }
}
