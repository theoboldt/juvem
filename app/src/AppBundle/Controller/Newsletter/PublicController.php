<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Newsletter;

use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Entity\User;
use AppBundle\Form\NewsletterSubscriptionType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;


class PublicController extends AbstractController
{
    /**
     * Page for creating a subscription or managing subscription as registered user
     *
     * @Route("/newsletter", name="newsletter_subscription")
     */
    public function newsletterSubscribeAction(Request $request)
    {
        $this->dieIfNewsletterNotEnabled();
        $subscriptionAvailable = false;

        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            $newsletterRepository = $this->ormManager->getRepository(
                NewsletterSubscription::class
            );
            $subscription         = $newsletterRepository->findOneByUser($user);
            if ($subscription) {
                $subscriptionAvailable = true;
            } else {
                $repository   = $this->getDoctrine()->getRepository(NewsletterSubscription::class);
                $subscription = $repository->findOneByEmail($user->getEmail());
                if ($subscription) {
                    $subscription->setAssignedUser($user);
                } else {
                    $subscription = new NewsletterSubscription();
                    $subscription->setEmail($user->getEmail());
                    $subscription->setNameLast($user->getNameLast());
                    $subscription->setAssignedUser($user);
                    $subscription->setIsConfirmed(true);  //no confirmation required for registered users
                }

            }
        } else {
            $subscription = new NewsletterSubscription();
        }

        $form = $this->createForm(NewsletterSubscriptionType::class, $subscription);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $subscription) {
            $tokenGenerator = $this->fosTokenGenerator;
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
                $mailManager = $this->newsletterManager;
                $mailManager->mailNewsletterSubscriptionRequested($subscription);

                $this->addFlash(
                    'success',
                    'Wir haben die Registrierung Ihres Newsletter-Abonnements entgegengenommen. Sie erhalten demnächst eine E-Mail, in der Sie das Abonnement noch bestätigen müssen.'
                );
                return $this->redirectToRoute('homepage');
            }
        }

        return $this->render(
            'newsletter/public/subscription.html.twig', ['form' => $form->createView()]
        );
    }

    /**
     * Manage a subscription via token
     *
     * @Route("/newsletter/{token}", requirements={"token": "[-\._[:alnum:]]{43}"},
     *                               name="newsletter_subscription_token")
     */
    public function newsletterSubscriptionViaTokenAction(Request $request)
    {
        $this->dieIfNewsletterNotEnabled();
        $token      = $request->get('token');
        $repository = $this->getDoctrine()->getRepository(NewsletterSubscription::class);

        /** @var NewsletterSubscription $subscription */
        $subscription = $repository->findOneByToken($token);

        if (!$subscription) {
            return $this->redirectToRoute('newsletter_subscription');
        }
        $em = $this->getDoctrine()->getManager();
        if (!$subscription->getIsConfirmed()) {
            $subscription->setIsConfirmed(true);
            $em->persist($subscription);
            $em->flush();
            $this->addFlash(
                'success',
                'Das Newsletter-Abonnement wurde erfolgreich bestätigt. Auf dieser Seite können Sie auch in Zukunft ihr Abonnement konfigurieren.'
            );
        }

        $form = $this->createForm(NewsletterSubscriptionType::class, $subscription);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $subscription) {

            $em->persist($subscription);
            $em->flush();

            $this->addFlash(
                'success',
                'Die Änderungen des Newsletter-Abonnements wurden gespeichert'
            );
        }

        return $this->render(
            'newsletter/public/subscription.html.twig', ['form' => $form->createView()]
        );
    }


    /**
     * Confirm a subscription
     *
     * @Route("/newsletter/{token}/confirm", requirements={"token": "[-\._[:alnum:]]{43}"},
     *                                       name="newsletter_subscription_confirm")
     */
    public function newsletterConfirmAction(Request $request)
    {
        $this->dieIfNewsletterNotEnabled();
        $token      = $request->get('token');
        $repository = $this->getDoctrine()->getRepository(NewsletterSubscription::class);
        /** @var NewsletterSubscription $subscription */
        $subscription = $repository->findOneByToken($token);

        if ($subscription) {
            $subscription->setIsConfirmed(true);
            $em = $this->getDoctrine()
                       ->getManager();
            $em->persist($subscription);
            $em->merge($subscription);

            $subscriptionListOther = $repository->findBy(['email' => $subscription->getEmail()]);
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
                'newsletter_subscription_token', ['token' => $subscription->getDisableToken()]
            );
        }
        return $this->redirectToRoute('newsletter_subscription');
    }
}