<?php
namespace AppBundle\Controller\Newsletter;


use AppBundle\Entity\Event;
use AppBundle\Entity\Newsletter;
use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Entity\User;
use AppBundle\Form\NewsletterMailType;
use AppBundle\Form\NewsletterSubscriptionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class AdminController extends Controller
{

    /**
     * Newsletter/Subscriptions overview page
     *
     * @Route("/admin/newsletter", name="newsletter_admin_overview")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function overviewAction()
    {
        return $this->render(
            'newsletter/admin/overview.html.twig'
        );
    }

    /**
     * List of all newsletter subscriptions
     *
     * @Route("/admin/newsletter/subscription/list", name="newsletter_admin_subscription_list")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function listSubscriptionsAction()
    {
        return $this->render(
            'newsletter/admin/subscription/list.html.twig'
        );
    }

    /**
     * Data provider for newsletter subscription list
     *
     * @see self::listSubscriptionsAction()
     * @Route("/admin/newsletter/subscription/list.json", name="newsletter_admin_subscription_list_data")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function listSubscriptionsDataAction()
    {
        $repository             = $this->getDoctrine()->getRepository('AppBundle:NewsletterSubscription');
        $subscriptionEntityList = $repository->findAll();
        $subscriptionList       = array();


        foreach ($subscriptionEntityList as $subscription) {
            $ageRangeBegin = $subscription->getAgeRangeBegin();
            $ageRangeEnd   = $subscription->getAgeRangeEnd();
            $userContent   = null;
            $user          = $subscription->getAssignedUser();
            if ($user) {
                $userContent = sprintf(
                    '<a href="%s">%s</a>',
                    $this->generateUrl('user_detail', array('uid' => $user->getUid())),
                    User::fullname($user->getNameLast(), $user->getNameFirst())
                );
            }

            $subscriptionList[] = array(
                'rid'           => $subscription->getRid(),
                'email'         => $subscription->getEmail(),
                'nameLast'      => $subscription->getNameLast(),
                'user'          => $userContent,
                'ageRangeBegin' => $ageRangeBegin,
                'ageRangeEnd'   => $ageRangeEnd,
                'ageRange'      => $this->renderView(
                    'newsletter/admin/age-range-progress.html.twig',
                    array(
                        'ageRangeBegin' => $ageRangeBegin,
                        'ageRangeEnd'   => $ageRangeEnd,
                        'ageRangeMin'   => NewsletterSubscription::AGE_RANGE_MIN,
                        'ageRangeMax'   => NewsletterSubscription::AGE_RANGE_MAX,
                    )
                )
            );
        }
        return new JsonResponse($subscriptionList);
    }

    /**
     * List newsletters (sent and drafts)
     *
     * @Route("/admin/newsletter/list", name="newsletter_admin_newsletter_list")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function listNewsletterAction()
    {
        return $this->render(
            'newsletter/admin/newsletter/list.html.twig'
        );
    }

    /**
     * Data provider for newsletter (message) list
     *
     * @see self::listNewsletterAction()
     * @Route("/admin/newsletter/list.json", name="newsletter_admin_newsletter_list_data")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function listNewsletterDataAction(Request $request)
    {
        $repository           = $this->getDoctrine()->getRepository('AppBundle:Newsletter');
        $newsletterEntityList = $repository->findAll();
        $newsletterList       = array();


        foreach ($newsletterEntityList as $newsletter) {
            $ageRangeBegin    = $newsletter->getAgeRangeBegin();
            $ageRangeEnd      = $newsletter->getAgeRangeEnd();
            $newsletterSentAt = $newsletter->getSentAt();

            $newsletterList[] = array(
                'lid'           => $newsletter->getLid(),
                'subject'       => $newsletter->getSubject(),
                'sentAt'        => $newsletterSentAt ? $newsletterSentAt->format(
                    'd.m.y H:i'
                ) : '<i>Entwurf</i>',
                'ageRangeBegin' => $ageRangeBegin,
                'ageRangeEnd'   => $ageRangeEnd,
                'ageRange'      => $this->renderView(
                    'newsletter/admin/age-range-progress.html.twig',
                    array(
                        'ageRangeBegin' => $ageRangeBegin,
                        'ageRangeEnd'   => $ageRangeEnd,
                        'ageRangeMin'   => NewsletterSubscription::AGE_RANGE_MIN,
                        'ageRangeMax'   => NewsletterSubscription::AGE_RANGE_MAX,
                    )
                )
            );
        }
        return new JsonResponse($newsletterList);
    }

    /**
     * Details of a single subscription
     *
     * @Route("/admin/newsletter/subscription/{rid}", requirements={"rid": "\d+"},
     *                                                name="newsletter_admin_subscription_detail")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function subscriptionDetailAction(Request $request)
    {
        $rid        = $request->get('rid');
        $repository = $this->getDoctrine()->getRepository('AppBundle:NewsletterSubscription');

        /** @var NewsletterSubscription $subscription */
        $subscription = $repository->findOneBy(array('rid' => $rid));
        $form         = $this->createForm(NewsletterSubscriptionType::class, $subscription);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $subscription) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($subscription);
            $em->flush();

            $this->addFlash(
                'success',
                'Die Änderungen des Newsletter-Abonnements wurden gespeichert'
            );
        }

        return $this->render(
            'newsletter/admin/subscription/details.html.twig',
            array('form' => $form->createView(), 'subscription' => $subscription)
        );
    }

    /**
     * Data provider for recipient count
     *
     * @Route("/admin/newsletter/affected-recipient-count", name="newsletter_admin_affected_recipient_count")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function affectedNewsletterRecipientAmountAction(Request $request)
    {
        $token              = $request->get('_token');
        $ageRangeBegin      = (int)$request->get('ageRangeBegin');
        $ageRangeEnd        = (int)$request->get('ageRangeEnd');
        $similarEventIdList = $request->get('events');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('newsletterSendDialog')) {
            throw new AccessDeniedHttpException('Invalid token');
        }

        $repository = $this->getDoctrine()->getRepository('AppBundle:NewsletterSubscription');
        $count      = count(
            $repository->qualifiedNewsletterSubscriptionIdList($ageRangeBegin, $ageRangeEnd, $similarEventIdList)
        );

        /** @var NewsletterSubscription $subscription */
        return new JsonResponse(
            array(
                'count' => $count
            )
        );
    }

    /**
     * Data provider for recipients
     *
     * @Route("/admin/newsletter/affected-recipient-list", name="newsletter_admin_affected_recipient")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function affectedNewsletterRecipientAction(Request $request)
    {
        $token              = $request->get('_token');
        $lid                = (int)$request->get('lid');
        $ageRangeBegin      = (int)$request->get('ageRangeBegin');
        $ageRangeEnd        = (int)$request->get('ageRangeEnd');
        $similarEventIdList = $request->get('events');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('newsletterSendDialog')) {
            throw new AccessDeniedHttpException('Invalid token');
        }

        $repository = $this->getDoctrine()->getRepository('AppBundle:NewsletterSubscription');
        $recipients = $repository->qualifiedNewsletterSubscriptionList(
            $ageRangeBegin, $ageRangeEnd, $similarEventIdList, $lid
        );

        $result = [];
        /** @var NewsletterSubscription $recipient */
        foreach ($recipients as $recipient) {
            $result[] = $recipient->getName();
        }

        return new JsonResponse($result);
    }

    /**
     * Send newsletter
     *
     * @Route("/admin/newsletter/send", name="newsletter_send")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function sendNewsletterAction(Request $request)
    {
        $token = $request->get('_token');
        $lid   = (int)$request->get('lid');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('newsletterSendDialog')) {
            throw new AccessDeniedHttpException('Invalid token');
        }

        $repository = $this->getDoctrine()->getRepository('AppBundle:Newsletter');
        $newsletter = $repository->findOneBy(['lid' => $lid]);

        if (!$newsletter) {
            throw new NotFoundHttpException('Requested newsletter not found by lid');
        }
        $eventIds = [];
        /** @var Event $event */
        foreach ($newsletter->getEvents() as $event) {
            $eventIds[] = $event->getEid();
        }
        $recipients = $this->getDoctrine()->getRepository('AppBundle:NewsletterSubscription')
                           ->qualifiedNewsletterSubscriptionList(
                               $newsletter->getAgeRangeBegin(), $newsletter->getAgeRangeEnd(), $eventIds,
                               $newsletter->getLid()
                           );

        $mailManager = $this->get('app.newsletter_manager');
        $sentCount   = $mailManager->mailNewsletter($newsletter, $recipients);

        $em = $this->getDoctrine()->getManager();
        $em->persist($newsletter);
        $em->flush();

        if ($sentCount) {
            $this->addFlash(
                'success',
                sprintf('Der Newsletter wurde erfolgreich an %d Empfänger versandt.', $sentCount)
            );
        } else {
            $this->addFlash(
                'warning',
                'Der Newsletter wurde an keinen Empfänger versandt.'
            );
        }

        return new JsonResponse(['sentCount' => $sentCount]);
    }

    /**
     * Create new newsletter page
     *
     * @Route("/admin/newsletter/create", name="newsletter_admin_create")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function createNewsletterAction(Request $request)
    {
        $newsletter = new Newsletter();
        $form       = $this->createForm(NewsletterMailType::class, $newsletter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($newsletter);
            $em->flush();
            $this->addFlash(
                'success',
                'Der Entwurf wurden gesichert'
            );

            return $this->redirectToRoute('newsletter_edit', array('lid' => 1));
        }

        return $this->render(
            'newsletter/admin/newsletter/new.html.twig',
            array('form' => $form->createView(), 'newsletter' => $newsletter)
        );
    }

    /**
     * Page for details of a newsletter
     *
     * @Route("/admin/newsletter/{lid}/edit", requirements={"lid": "\d"}, name="newsletter_edit")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function detailedNewsletterAction(Request $request)
    {
        $lid        = $request->get('lid');
        $repository = $this->getDoctrine()->getRepository('AppBundle:Newsletter');

        /** @var Newsletter $newsletter */
        $newsletter = $repository->findOneBy(array('lid' => $lid));
        $form       = $this->createForm(NewsletterMailType::class, $newsletter);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($newsletter);
            $em->flush();

            $this->addFlash(
                'success',
                'Die Änderungen am Newsletter wurden gesichert'
            );

            return $this->redirectToRoute('newsletter_edit', array('lid' => $newsletter->getLid()));
        }

        return $this->render(
            'newsletter/admin/newsletter/edit.html.twig',
            array('form' => $form->createView(), 'newsletter' => $newsletter)
        );
    }
}