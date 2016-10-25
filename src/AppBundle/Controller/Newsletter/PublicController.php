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
     * @Route("/newsletter", name="newsletter_subscription")
     */
    public function newsletterSettings(Request $request)
    {
        $subscriptionAvailable = false;

        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            $newsletterSubscription = $user->getAssignedNewsletterSubscription();
            if ($newsletterSubscription) {
                $subscriptionAvailable = true;
            } else {
                $newsletterSubscription = new NewsletterSubscription();
                $newsletterSubscription->setEmail($user->getEmail());
                $newsletterSubscription->setAssignedUser($user);
                $newsletterSubscription->setIsConfirmed(true);  //no confirmation required for registered users
            }
        } else {
            $newsletterSubscription = new NewsletterSubscription();
        }

        $form = $this->createForm(NewsletterSubscriptionType::class, $newsletterSubscription);

        $form->handleRequest($request);
        if ($form->isValid() && $form->isSubmitted() && $newsletterSubscription) {
            $tokenGenerator = $this->get('fos_user.util.token_generator');
            $newsletterSubscription->setDisableToken($tokenGenerator->generateToken());

            $em = $this->getDoctrine()
                       ->getManager();

            $em->persist($newsletterSubscription);
            $em->flush();

            if ($subscriptionAvailable) {
                $this->addFlash(
                    'success',
                    'Die Änderungen des Rundbrief-Abonnements wurden gespeichert'
                );
            } else {
                $this->addFlash(
                    'success',
                    'Wir haben die Registrierung Ihres Rundbrief-Abonnements entgegengenommen. Sie erhalten demnächst eine E-Mail, in der Sie das Abonnement noch bestätigen müssen.'
                );
            }
        }

        return $this->render(
            'newsletter/public/subscription.html.twig', array(
                                                      'form' => $form->createView()
                                                  )
        );
    }

    /**
     * Page for details of an event
     *
     * @Route("/newsletter/{disable_token}", requirements={"disable_token": "[:alnum:]{43}"},
     *                                       name="newsletter_subscription_token")
     */
    public function newsletterSettingsToken(Request $request)
    {
        //TODO implementation
    }
}