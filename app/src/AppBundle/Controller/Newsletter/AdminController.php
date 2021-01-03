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


use AppBundle\Entity\Event;
use AppBundle\Entity\Newsletter;
use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Form\NewsletterMailType;
use AppBundle\Form\NewsletterSubscriptionType;
use AppBundle\InvalidTokenHttpException;
use AppBundle\Manager\NewsletterManager;
use AppBundle\Twig\MailGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;


class AdminController extends AbstractController
{
    /**
     * app.twig_mail_generator
     *
     * @var MailGenerator
     */
    private MailGenerator $twigMailGenerator;
    
    /**
     * AbstractController constructor.
     *
     * @param Environment $twig
     * @param ManagerRegistry $doctrine
     * @param FormFactoryInterface $formFactory
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     * @param RouterInterface $router
     * @param bool $newsletterFeature
     * @param string $customizationOrganizationName
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param SessionInterface $session
     * @param TokenGeneratorInterface $fosTokenGenerator
     * @param NewsletterManager $newsletterManager
     * @param EntityManagerInterface $ormManager
     * @param MailGenerator $twigMailGenerator
     */
    public function __construct(
        Environment $twig,
        ManagerRegistry $doctrine,
        FormFactoryInterface $formFactory,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        RouterInterface $router,
        bool $newsletterFeature,
        string $customizationOrganizationName,
        CsrfTokenManagerInterface $csrfTokenManager,
        SessionInterface $session,
        TokenGeneratorInterface $fosTokenGenerator,
        NewsletterManager $newsletterManager,
        EntityManagerInterface $ormManager,
        MailGenerator $twigMailGenerator
    )
    {
        parent::__construct(
            $twig,
            $doctrine,
            $formFactory,
            $authorizationChecker,
            $tokenStorage,
            $router,
            $newsletterFeature,
            $customizationOrganizationName,
            $csrfTokenManager,
            $session,
            $fosTokenGenerator,
            $newsletterManager,
            $ormManager
        );
        $this->twigMailGenerator = $twigMailGenerator;
    }

    /**
     * Newsletter/Subscriptions overview page
     *
     * @Route("/admin/newsletter", name="newsletter_admin_overview")
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function overviewAction()
    {
        $this->dieIfNewsletterNotEnabled();
        return $this->render(
            'newsletter/admin/overview.html.twig'
        );
    }

    /**
     * List of all newsletter subscriptions
     *
     * @Route("/admin/newsletter/subscription/list", name="newsletter_admin_subscription_list")
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function listSubscriptionsAction()
    {
        $this->dieIfNewsletterNotEnabled();
        return $this->render(
            'newsletter/admin/subscription/list.html.twig'
        );
    }

    /**
     * Data provider for newsletter subscription list
     *
     * @see self::listSubscriptionsAction()
     * @Route("/admin/newsletter/subscription/list.json", name="newsletter_admin_subscription_list_data")
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function listSubscriptionsDataAction()
    {
        $this->dieIfNewsletterNotEnabled();
        $repository             = $this->getDoctrine()->getRepository(NewsletterSubscription::class);
        $subscriptionEntityList = $repository->findAll();
        $subscriptionList       = [];

        /** @var NewsletterSubscription $subscription */
        foreach ($subscriptionEntityList as $subscription) {
            $ageRangeBegin = $subscription->getAgeRangeBegin();
            $ageRangeEnd   = $subscription->getAgeRangeEnd();
            $userContent   = null;
            $user          = $subscription->getAssignedUser();
            if ($user) {
                $userContent = sprintf(
                    '<a href="%s">%s</a>',
                    $this->generateUrl('user_detail', ['uid' => $user->getUid()]),
                    $user->fullname()
                );
            }

            $subscriptionList[] = [
                'rid'           => $subscription->getRid(),
                'is_enabled'    => (int)$subscription->getIsEnabled(),
                'is_confirmed'  => (int)$subscription->getIsConfirmed(),
                'email'         => $subscription->getEmail(),
                'nameLast'      => $subscription->getNameLast(),
                'user'          => $userContent,
                'ageRangeBegin' => $ageRangeBegin,
                'ageRangeEnd'   => $ageRangeEnd,
                'ageRange'      => $this->renderView(
                    'newsletter/admin/age-range-progress.html.twig',
                    [
                        'ageRangeBegin' => $ageRangeBegin,
                        'ageRangeEnd'   => $ageRangeEnd,
                        'ageRangeMin'   => NewsletterSubscription::AGE_RANGE_MIN,
                        'ageRangeMax'   => NewsletterSubscription::AGE_RANGE_MAX,
                    ]
                ),
            ];
        }
        return new JsonResponse($subscriptionList);
    }

    /**
     * List newsletters (sent and drafts)
     *
     * @Route("/admin/newsletter/list", name="newsletter_admin_newsletter_list")
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function listNewsletterAction()
    {
        $this->dieIfNewsletterNotEnabled();
        return $this->render(
            'newsletter/admin/newsletter/list.html.twig'
        );
    }

    /**
     * Data provider for newsletter (message) list
     *
     * @see self::listNewsletterAction()
     * @Route("/admin/newsletter/list.json", name="newsletter_admin_newsletter_list_data")
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function listNewsletterDataAction(Request $request)
    {
        $this->dieIfNewsletterNotEnabled();
        $repository           = $this->getDoctrine()->getRepository(Newsletter::class);
        $newsletterEntityList = $repository->findAll();
        $newsletterList       = [];


        foreach ($newsletterEntityList as $newsletter) {
            $ageRangeBegin    = $newsletter->getAgeRangeBegin();
            $ageRangeEnd      = $newsletter->getAgeRangeEnd();
            $newsletterSentAt = $newsletter->getSentAt();

            $newsletterList[] = [
                'lid'           => $newsletter->getLid(),
                'subject'       => $newsletter->getSubject(),
                'sentAt'        => $newsletterSentAt ? $newsletterSentAt->format(
                    'd.m.y H:i'
                ) : '<i>Entwurf</i>',
                'ageRangeBegin' => $ageRangeBegin,
                'ageRangeEnd'   => $ageRangeEnd,
                'ageRange'      => $this->renderView(
                    'newsletter/admin/age-range-progress.html.twig',
                    [
                        'ageRangeBegin' => $ageRangeBegin,
                        'ageRangeEnd'   => $ageRangeEnd,
                        'ageRangeMin'   => NewsletterSubscription::AGE_RANGE_MIN,
                        'ageRangeMax'   => NewsletterSubscription::AGE_RANGE_MAX,
                    ]
                ),
            ];
        }
        return new JsonResponse($newsletterList);
    }

    /**
     * Details of a single subscription
     *
     * @ParamConverter("subscription", class="AppBundle:NewsletterSubscription", options={"id" = "rid"})
     * @Route("/admin/newsletter/subscription/{rid}", requirements={"rid": "\d+"},
     *                                                name="newsletter_admin_subscription_detail")
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function subscriptionDetailAction(Request $request, NewsletterSubscription $subscription)
    {
        $this->dieIfNewsletterNotEnabled();
        $form = $this->createForm(NewsletterSubscriptionType::class, $subscription);

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
            ['form' => $form->createView(), 'subscription' => $subscription]
        );
    }

    /**
     * Force confirmation of an subscription
     *
     * @ParamConverter("subscription", class="AppBundle:NewsletterSubscription", options={"id" = "rid"})
     * @Route("/admin/newsletter/subscription/{rid}/forceconfirmation", requirements={"rid": "\d+"},
     *                                                name="newsletter_admin_subscription_confirmation")
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function subscriptionForceConfirmationAction(Request $request, NewsletterSubscription $subscription)
    {
        $this->dieIfNewsletterNotEnabled();
        $token     = $request->get('_token');
        $confirmed = (int)$request->get('confirmed');


        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->csrfTokenManager;
        if ($token != $csrf->getToken('subscription-confirmation-' . $subscription->getRid())) {
            throw new InvalidTokenHttpException();
        }

        $em = $this->getDoctrine()->getManager();
        $subscription->setIsConfirmed(($confirmed == 1));
        $em->persist($subscription);
        $em->flush();

        return new JsonResponse(['confirmed' => (int)$subscription->getIsConfirmed()]);
    }

    /**
     * Data provider for recipient count
     *
     * @Route("/admin/newsletter/affected-recipient-count", name="newsletter_admin_affected_recipient_count")
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function affectedNewsletterRecipientAmountAction(Request $request)
    {
        $this->dieIfNewsletterNotEnabled();
        $token              = $request->get('_token');
        $ageRangeBegin      = (int)$request->get('ageRangeBegin');
        $ageRangeEnd        = (int)$request->get('ageRangeEnd');
        $similarEventIdList = $request->get('events');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->csrfTokenManager;
        if ($token != $csrf->getToken('newsletterSendDialog')) {
            throw new InvalidTokenHttpException();
        }

        $repository = $this->getDoctrine()->getRepository(NewsletterSubscription::class);
        $count      = count(
            $repository->qualifiedNewsletterSubscriptionIdList($ageRangeBegin, $ageRangeEnd, $similarEventIdList)
        );

        /** @var NewsletterSubscription $subscription */
        return new JsonResponse(
            [
                'count' => $count,
            ]
        );
    }

    /**
     * Data provider for recipients
     *
     * @Route("/admin/newsletter/affected-recipient-list", name="newsletter_admin_affected_recipient")
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function affectedNewsletterRecipientAction(Request $request)
    {
        $this->dieIfNewsletterNotEnabled();
        $token              = $request->get('_token');
        $lid                = (int)$request->get('lid');
        $ageRangeBegin      = (int)$request->get('ageRangeBegin');
        $ageRangeEnd        = (int)$request->get('ageRangeEnd');
        $similarEventIdList = $request->get('events');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->csrfTokenManager;
        if ($token != $csrf->getToken('newsletterSendDialog')) {
            throw new InvalidTokenHttpException();
        }

        $repository = $this->getDoctrine()->getRepository(NewsletterSubscription::class);
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
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function sendNewsletterAction(Request $request)
    {
        $this->dieIfNewsletterNotEnabled();
        $token = $request->get('_token');
        $lid   = (int)$request->get('lid');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->csrfTokenManager;
        if ($token != $csrf->getToken('newsletterSendDialog')) {
            throw new InvalidTokenHttpException();
        }

        $repository = $this->getDoctrine()->getRepository(Newsletter::class);
        $newsletter = $repository->findOneBy(['lid' => $lid]);

        if (!$newsletter) {
            throw new NotFoundHttpException('Requested newsletter not found by lid');
        }
        $eventIds = [];
        /** @var Event $event */
        foreach ($newsletter->getEvents() as $event) {
            $eventIds[] = $event->getEid();
        }
        $recipients = $this->getDoctrine()->getRepository(NewsletterSubscription::class)
                           ->qualifiedNewsletterSubscriptionList(
                               $newsletter->getAgeRangeBegin(), $newsletter->getAgeRangeEnd(), $eventIds,
                               $newsletter->getLid()
                           );

        $mailManager = $this->newsletterManager;
        $sentCount   = $mailManager->mailNewsletter($newsletter, $recipients);
        
        if ($newsletter->getSentAt()) {
            $newsletter->setSentAt(new \DateTime());
        }
        
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
     * @param Request $request
     * @Route("/admin/newsletter/create_preview", name="newsletter_preview", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     * @return Response
     */
    public function previewNewsletterAction(Request $request): Response
    {
        $this->dieIfNewsletterNotEnabled();

        $newsletter = new Newsletter();
        $form = $this->createForm(NewsletterMailType::class, $newsletter);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $title   = $newsletter->getTitle();
            $lead    = $newsletter->getLead();
            $content = $newsletter->getContent();
        }  else {
            $title   = 'Gute Nachrichten';
            $lead    = 'Ein neuer Newsletter ist verfügbar!';
            $content = "Text hier einfügen. Eine Leerzeile führt zu einem Absatz, ein einfacher Zeilenabsatz wird zusammengefasst werden. Text in zwei Sternchen einfassen, damit er **hervorgehoben** wird.\n\nMit besten Grüßen,\n\n*" .
            $this->customizationOrganizationName . "*";
        }

        $data = [
            'calltoactioncontent' => '',
            'subject'             => 'Test',
            'title'               => $title,
            'lead'                => $lead,
            'content'             => $content,
        ];

        $dataText = [];
        $dataHtml = [];

        $content = null;
        foreach ($data as $area => $content) {
            $dataText[$area] = strip_tags($content);
            $dataHtml[$area] = $content;
        }
        unset($content);
        
        $dataBoth = [
            'text' => $dataText,
            'html' => $dataHtml,
        ];
        
        $message = $this->twigMailGenerator->renderHtml('general-markdown', $dataBoth);
        return new Response($message);
    }

    /**
     * Send newsletter
     *
     * @Route("/admin/newsletter/send_test", name="newsletter_send_test")
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function sendNewsletterTestAction(Request $request)
    {
        $this->dieIfNewsletterNotEnabled();
        $token   = $request->get('_token');
        $subject = $request->get('subject');
        $title   = $request->get('title');
        $lead    = $request->get('lead');
        $content = $request->get('content');
        $email   = $request->get('email');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->csrfTokenManager;
        if ($token != $csrf->getToken('newsletterSendDialogTest')) {
            throw new InvalidTokenHttpException();
        }

        $newsletter = new Newsletter();
        $newsletter->setSubject($subject)
                   ->setTitle($title)
                   ->setLead($lead)
                   ->setContent($content);

        $recipient = new NewsletterSubscription();
        $recipient->setEmail($email)
                  ->setNameLast('Muster')
                  ->setIsConfirmed(true)
                  ->setIsEnabled(true);

        $mailManager = $this->newsletterManager;
        $sentCount   = $mailManager->mailNewsletter($newsletter, [$recipient]);

        return new JsonResponse(['sentCount' => $sentCount]);
    }

    /**
     * Create new newsletter page
     *
     * @Route("/admin/newsletter/create", name="newsletter_admin_create")
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function createNewsletterAction(Request $request)
    {
        $this->dieIfNewsletterNotEnabled();
        $newsletter = new Newsletter();
        $newsletter->setTitle('Gute Nachrichten');
        $newsletter->setLead('Ein neuer Newsletter ist verfügbar!');
        $newsletter->setContent(
            "Text hier einfügen. Eine Leerzeile führt zu einem Absatz, ein einfacher Zeilenabsatz wird zusammengefasst werden. Text in zwei Sternchen einfassen, damit er **hervorgehoben** wird.\n\nMit besten Grüßen,\n\n*" .
            $this->customizationOrganizationName . "*"
        );

        $form = $this->createForm(NewsletterMailType::class, $newsletter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($newsletter);
            $em->flush();
            $this->addFlash(
                'success',
                'Der Entwurf wurden gesichert'
            );

            return $this->redirectToRoute('newsletter_edit', ['lid' => $newsletter->getLid()]);
        }

        return $this->render(
            'newsletter/admin/newsletter/new.html.twig',
            ['form' => $form->createView(), 'newsletter' => $newsletter]
        );
    }

    /**
     * Page for details of a newsletter
     *
     * @ParamConverter("newsletter", class="AppBundle:Newsletter", options={"id" = "lid"})
     * @Route("/admin/newsletter/{lid}/edit", requirements={"lid": "\d+"}, name="newsletter_edit")
     * @Security("is_granted('ROLE_ADMIN_NEWSLETTER')")
     */
    public function detailedNewsletterAction(Request $request, Newsletter $newsletter)
    {
        $this->dieIfNewsletterNotEnabled();
        $form = $this->createForm(NewsletterMailType::class, $newsletter);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($newsletter);
            $em->flush();

            $this->addFlash(
                'success',
                'Die Änderungen am Newsletter wurden gesichert'
            );

            return $this->redirectToRoute('newsletter_edit', ['lid' => $newsletter->getLid()]);
        }

        return $this->render(
            'newsletter/admin/newsletter/edit.html.twig',
            ['form' => $form->createView(), 'newsletter' => $newsletter]
        );
    }
}
