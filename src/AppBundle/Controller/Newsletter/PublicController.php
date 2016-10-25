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
     * Page for creating a subscription or managing subscription as registered user
     *
     * @Route("/newsletter", name="newsletter_subscription")
     */
    public function newsletterSubscribe(Request $request)
    {
        $subscriptionAvailable = false;

        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            $subscription = $user->getAssignedNewsletterSubscription();
            if ($subscription) {
                $subscriptionAvailable = true;
            } else {
                $subscription = new NewsletterSubscription();
                $subscription->setEmail($user->getEmail());
                $subscription->setAssignedUser($user);
                $subscription->setIsConfirmed(true);  //no confirmation required for registered users
            }
        } else {
            $subscription = new NewsletterSubscription();
        }

        $form = $this->createForm(NewsletterSubscriptionType::class, $subscription);

        $form->handleRequest($request);
        if ($form->isValid() && $form->isSubmitted() && $subscription) {
            $tokenGenerator = $this->get('fos_user.util.token_generator');
            $subscription->setDisableToken($tokenGenerator->generateToken());

            $em = $this->getDoctrine()
                       ->getManager();

            $em->persist($subscription);
            $em->flush();

            if ($subscriptionAvailable) {
                $this->addFlash(
                    'success',
                    'Die Änderungen des Newsletter-Abonnements wurden gespeichert'
                );
            } else {
                $participationManager = $this->get('app.newsletter_manager');
                $participationManager->mailNewsletterSubscriptionRequested($subscription);

                $this->addFlash(
                    'success',
                    'Wir haben die Registrierung Ihres Newsletter-Abonnements entgegengenommen. Sie erhalten demnächst eine E-Mail, in der Sie das Abonnement noch bestätigen müssen.'
                );
                return $this->redirectToRoute('homepage');
            }
        }

        return $this->render(
            'newsletter/public/subscription.html.twig', array('form' => $form->createView())
        );
    }

    /**
     * Manage a subscription via token
     *
     * @Route("/newsletter/{token}", requirements={"token": "[-\._[:alnum:]]{43}"},
     *                               name="newsletter_subscription_token")
     */
    public function newsletterSubscriptionViaToken(Request $request)
    {
        $token      = $request->get('token');
        $repository = $this->getDoctrine()->getRepository('AppBundle:NewsletterSubscription');

        /** @var NewsletterSubscription $subscription */
        $subscription = $repository->findOneBy(array('disableToken' => $token));

        if (!$subscription) {
            return $this->redirectToRoute('newsletter_subscription');
        }
        $form = $this->createForm(NewsletterSubscriptionType::class, $subscription);

        $form->handleRequest($request);
        if ($form->isValid() && $form->isSubmitted() && $subscription) {
            $em = $this->getDoctrine()
                       ->getManager();

            $em->persist($subscription);
            $em->flush();

            $this->addFlash(
                'success',
                'Die Änderungen des Newsletter-Abonnements wurden gespeichert'
            );
        }

        return $this->render(
            'newsletter/public/subscription.html.twig', array('form' => $form->createView())
        );
    }


    /**
     * Confirm a subscription
     *
     * @Route("/newsletter/{token}/confirm", requirements={"token": "[-\._[:alnum:]]{43}"},
     *                                       name="newsletter_subscription_confirm")
     */
    public function newsletterConfirm(Request $request)
    {
        $token      = $request->get('token');
        $repository = $this->getDoctrine()->getRepository('AppBundle:NewsletterSubscription');
        /** @var NewsletterSubscription $subscription */
        $subscription = $repository->findOneBy(array('disableToken' => $token));

        if ($subscription) {
            $subscription->setIsConfirmed(true);
            $em = $this->getDoctrine()
                       ->getManager();
            $em->persist($subscription);
            $em->merge($subscription);

            $subscriptionListOther = $repository->findBy(array('email' => $subscription->getEmail()));
            foreach ($subscriptionListOther as $subscriptionToDelete) {
                if ($subscription->getRid() != $subscriptionToDelete->getRid()) {
                    $em->remove($subscriptionToDelete);
                }
            }
            $em->flush();

            $this->addFlash(
                'success',
                'Das Newsletter-Abonnement wurde erfolgreich bestätigt. Auf dieser Seite können Sie auch in Zukunft ihr Abonnement konfigurieren.'
            );
            return $this->redirectToRoute(
                'newsletter_subscription_token', array('token' => $subscription->getDisableToken())
            );
        }
        return $this->redirectToRoute('newsletter_subscription');
    }
}