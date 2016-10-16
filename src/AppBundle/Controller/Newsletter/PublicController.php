<?php
namespace AppBundle\Controller\Newsletter;


use AppBundle\Entity\NewsletterRecipient;
use AppBundle\Entity\User;
use AppBundle\Form\NewsletterRecipientType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class PublicController extends Controller
{
    /**
     * Page for details of an event
     *
     * @Route("/newsletter", name="public_newsletter")
     */
    public function newsletterSettings(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            $newsletterRecipient = $user->getAssignedNewsletter();
            if (!$newsletterRecipient) {
                $newsletterRecipient = new NewsletterRecipient();
                $newsletterRecipient->setEmail($user->getEmail());
                $newsletterRecipient->setAssignedUser($user);
            }
        } else {
            $newsletterRecipient = new NewsletterRecipient();
        }

        $form = $this->createForm(NewsletterRecipientType::class, $newsletterRecipient);

        $form->handleRequest($request);
        if ($form->isValid() && $form->isSubmitted() && $newsletterRecipient) {
            $em = $this->getDoctrine()
                       ->getManager();

            $em->persist($newsletterRecipient);
            $em->flush();

            $this->addFlash(
                'success',
                'Die Änderungen wurden gespeichert'
            );
        }

        return $this->render(
            'newsletter/public/settings.html.twig', array(
                                                      'form' => $form->createView()

                                                  )
        );
    }
}