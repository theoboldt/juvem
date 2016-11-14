<?php
namespace AppBundle\Controller\Newsletter;


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


class AdminController extends Controller
{

    /**
     * @Route("/admin/newsletter", name="newsletter_admin_overview")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function overviewAction(Request $request)
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
     * @see self::listSubscriptionsAction()
     * @Route("/admin/newsletter/subscription/list.json", name="newsletter_admin_subscription_list_data")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function listSubscriptionsDataAction(Request $request)
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
        if ($form->isValid() && $form->isSubmitted() && $subscription) {
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
     * Page for details of an event
     *
     * @Route("/admin/newsletter/affected-recipient-count", name="newsletter_admin_affected_recipient_count")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function affectedNewsletterRecipientAmountAction(Request $request)
    {
        $ageRangeBegin      = $request->get('ageRangeBegin');
        $ageRangeEnd        = $request->get('ageRangeEnd');
        $similarEventIdList = $request->get('events');

        $repository = $this->getDoctrine()->getRepository('AppBundle:NewsletterSubscription');
        $count      = $repository->qualifiedNewsletterSubscriptionCount(
            $ageRangeBegin, $ageRangeEnd, $similarEventIdList
        );

        /** @var NewsletterSubscription $subscription */
        return new JsonResponse(
            array(
                'count' => $count
            )
        );

    }

    /**
     * Page for details of an event
     *
     * @Route("/admin/newsletter/create", name="newsletter_admin_create")
     * @Security("has_role('ROLE_ADMIN_NEWSLETTER')")
     */
    public function createNewsletterAction(Request $request)
    {
        $newsletter = new Newsletter();
        $form       = $this->createForm(NewsletterMailType::class, $newsletter);
        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
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
     * Page for details of an event
     *
     * @Route("/admin/newsletter/edit/{lid}", requirements={"lid": "\d"},
     *                                       name="newsletter_edit")
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

        if ($form->isValid() && $form->isSubmitted()) {
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