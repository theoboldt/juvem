<?php
namespace AppBundle\Controller\Newsletter;


use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Entity\User;
use AppBundle\Form\NewsletterSubscriptionType;
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
            $newsletterSubscription = $user->getAssignedNewsletter();
            if (!$newsletterSubscription) {
                $newsletterSubscription = new NewsletterSubscription();
                $newsletterSubscription->setEmail($user->getEmail());
                $newsletterSubscription->setAssignedUser($user);
            }
        } else {
            $newsletterSubscription = new NewsletterSubscription();
        }

        $form = $this->createForm(NewsletterSubscriptionType::class, $newsletterSubscription);

        $form->handleRequest($request);
        if ($form->isValid() && $form->isSubmitted() && $newsletterSubscription) {
            $em = $this->getDoctrine()
                       ->getManager();

            $em->persist($newsletterSubscription);
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