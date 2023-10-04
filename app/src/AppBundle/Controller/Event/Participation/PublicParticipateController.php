<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Event\Participation;

use AppBundle\Controller\AuthorizationAwareControllerTrait;
use AppBundle\Controller\DoctrineAwareControllerTrait;
use AppBundle\Controller\Event\WaitingListFlashTrait;
use AppBundle\Controller\FlashBagAwareControllerTrait;
use AppBundle\Controller\FormAwareControllerTrait;
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Controller\RoutingControllerTrait;
use AppBundle\Entity\Event;
use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use AppBundle\Form\ParticipationType;
use AppBundle\Manager\ParticipationManager;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class PublicParticipateController
{
    use RoutingControllerTrait, AuthorizationAwareControllerTrait, FormAwareControllerTrait, RenderingControllerTrait, WaitingListFlashTrait, DoctrineAwareControllerTrait, FlashBagAwareControllerTrait;
    
    /**
     * feature.newsletter
     *
     * @var bool
     */
    private bool $featureNewsletter;
    
    
    /**
     * feature.registration
     *
     * @var bool
     */
    private bool $featureRegistration;

    /**
     * @var int 
     */
    private int $requiredParticipationPhoneNumberCount;
    
    /**
     * app.participation_manager
     *
     * @var ParticipationManager
     */
    private ParticipationManager $participationManager;
    
    /**
     * AdminController constructor.
     *
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     * @param ManagerRegistry $doctrine
     * @param RouterInterface $router
     * @param Environment $twig
     * @param FormFactoryInterface $formFactory
     * @param SessionInterface $session
     * @param string $featureNewsletter
     * @param string $featureRegistration
     * @param string $requiredParticipationPhoneNumberCount
     * @param ParticipationManager $participationManager
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        ManagerRegistry $doctrine,
        RouterInterface $router,
        Environment $twig,
        FormFactoryInterface $formFactory,
        SessionInterface $session,
        string $featureNewsletter,
        string $featureRegistration,
        string $requiredParticipationPhoneNumberCount,
        ParticipationManager $participationManager
    )
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage         = $tokenStorage;
        $this->doctrine             = $doctrine;
        $this->router               = $router;
        $this->twig                 = $twig;
        $this->formFactory          = $formFactory;
        $this->session              = $session;
        $this->featureNewsletter    = $featureNewsletter;
        $this->featureRegistration  = $featureRegistration;
        $this->participationManager = $participationManager;
        $this->requiredParticipationPhoneNumberCount = (int)$requiredParticipationPhoneNumberCount;
    }
    
    /**
     * Page for list of events
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/participate", requirements={"eid": "\d+"}, name="event_public_participate")
     */
    public function participateAction(Event $event, Request $request)
    {
        $eid = $event->getEid();

        if (!$event->isActive()) {
            $this->addFlash(
                'danger',
                'Für die Veranstaltung <i>' . htmlspecialchars($event->getTitle()) .
                '</i> werden im Moment keine Anmeldungen erfasst'
            );

            return $this->redirectToRoute('homepage');
        }
        $participation = new Participation($event);

        /** @var \AppBundle\Entity\User $user */
        $user = $this->getUser();
        if ($user) {
            $participation->setNameLast($user->getNameLast());
            $participation->setNameFirst($user->getNameFirst());
        }
        $form = $this->createForm(
            ParticipationType::class,
            $participation,
            [
                ParticipationType::ACQUISITION_FIELD_PUBLIC  => true,
                ParticipationType::ACQUISITION_FIELD_PRIVATE => false,
                ParticipationType::PHONE_NUMBER_MIN_COUNT    => $this->requiredParticipationPhoneNumberCount,
            ]
        );

        if ($request->request->has('participation')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $participationData = $request->request->get('participation', null);
                $request->getSession()->set('participation-data-' . $eid, $participationData);

                return $this->redirectToRoute('event_public_participate_confirm', ['eid' => $eid]);
            }
        } elseif ($request->getSession()->get('participation-data-' . $eid, null)) {
            $participationData = $request->getSession()->get('participation-data-' . $eid);
            $form->submit($participationData);
            $participation->setEvent($event);
        }

        $this->addWaitingListFlashIfRequired($event);

        $user           = $this->getUser();
        $participations = [];
        if ($user) {
            $participations = $user->getAssignedParticipations();
        }

        return $this->render(
            'event/participation/public/begin.html.twig',
            [
                'event'                          => $event,
                'acquisitionFieldsParticipation' => $event->getAcquisitionAttributes(true, false, false, false, true),
                'participations'                 => $participations,
                'acquisitionFieldsParticipant'   => $event->getAcquisitionAttributes(false, true, false, false, true),
                'form'                           => $form->createView(),
            ]
        );
    }

    /**
     * Page for list of events
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/participate/prefill/{pid}", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                  name="event_public_participate_prefill")
     */
    public function participatePrefillAction(Event $event, $pid, Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash(
                'warning',
                sprintf(
                    'Um Daten einer früherer Anmeldung verwenden zu können, müssen Sie angemeldet sein. Sie können sich jetzt <a href="%s">anmelden</a>, oder die Daten hier direkt eingeben.',
                    $this->generateUrl('fos_user_security_login')
                )
            );
            return $this->redirectToRoute('event_public_participate', ['eid' => $event->getEid()]);
        }

        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participationPrevious   = $participationRepository->findOneBy(
            ['pid' => $pid, 'assignedUser' => $user->getUid()]
        );

        $participation = Participation::createFromTemplateForEvent($participationPrevious, $event);
        $participation->setAssignedUser($user);
        $form = $this->createForm(
            ParticipationType::class,
            $participation,
            [
                ParticipationType::ACQUISITION_FIELD_PUBLIC  => true,
                ParticipationType::ACQUISITION_FIELD_PRIVATE => false,
            ]
        );
        $eid = $event->getEid();
        if ($request->request->has('participation')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $participationData = $request->request->get('participation', null);
                $request->getSession()->set('participation-data-' . $eid, $participationData);

                return $this->redirectToRoute('event_public_participate_confirm', ['eid' => $eid]);
            }
        } elseif ($request->getSession()->get('participation-data-' . $eid, null)) {
            $participationData = $request->getSession()->get('participation-data-' . $eid);
            $form->submit($participationData);
            $participation->setEvent($event);
        } else {
            $this->addFlash(
                'success',
                'Die Anmeldung wurde mit Daten einer früheren Anmeldung vorausgefüllt. Bitte überprüfen Sie sorgfältig ob die Daten noch richtig sind.'
            );
        }

        $this->addWaitingListFlashIfRequired($event);
        
        if (!$participationPrevious) {
            $this->addFlash(
                'danger',
                'Es konnte keine passende Anmeldung von Ihnen gefunden werden, mit der das Anmeldeformular hätte vorausgefüllt werden können.'
            );
            return $this->redirectToRoute('event_public_participate', ['eid' => $event->getEid()]);
        }

        $user           = $this->getUser();
        $participations = [];
        if ($user) {
            $participations = $user->getAssignedParticipations();
        }

        return $this->render(
            'event/participation/public/begin.html.twig',
            [
                'event'                          => $event,
                'acquisitionFieldsParticipation' => $event->getAcquisitionAttributes(true, false, false, false, true),
                'participations'                 => $participations,
                'acquisitionFieldsParticipant'   => $event->getAcquisitionAttributes(false, true, false, false, true),
                'form'                           => $form->createView(),
            ]
        );
    }

    /**
     * Page summary and confirmation
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/participate/confirm", requirements={"eid": "\d+"}, name="event_public_participate_confirm")
     */
    public function confirmParticipationAction(Event $event, Request $request)
    {
        $eid               = $event->getEid();
        $participationData = $request->getSession()->get('participation-data-' . $eid, null);
        if (!$participationData) {
            return $this->redirectToRoute('event_public_participate', ['eid' => $eid]);
        }

        $participation = new Participation($event);
        $form          = $this->createForm(
            ParticipationType::class,
            $participation,
            [
                ParticipationType::ACQUISITION_FIELD_PUBLIC  => true,
                ParticipationType::ACQUISITION_FIELD_PRIVATE => false,
            ]
        );
        $form->submit($participationData);
        if ($request->query->has('confirm')) {
            if (!$form->isSubmitted() || !$form->isValid()) {
                return $this->redirectToRoute('event_public_participate', ['eid' => $eid]);
            }

            $user                 = $this->getUser();
            $managedParticipation = $this->participationManager->receiveParticipationRequest(
                $participation, ($user instanceof User) ? $user : null
            );

            $participationManager = $this->participationManager;
            $participationManager->mailParticipationRequested($managedParticipation, $event);

            $request->getSession()->remove('participation-data-' . $eid);

            if ($request->getSession()->has('participationList')) {
                $participationList = $request->getSession()->get('participationList');
            } else {
                $participationList = [];
            }
            $participationList[] = $managedParticipation->getPid();
            $request->getSession()
                    ->set('participationList', $participationList);

            $message
                = '<p>Wir haben Ihren Teilnahmewunsch festgehalten. Sie erhalten eine automatische Bestätigung, dass die Anfrage bei uns eingegangen ist.</p>';

            if ($this->featureRegistration && !$user) {
                $message .= sprintf(
                    '<p>Sie können sich jetzt <a href="%s">registrieren</a>. Dadurch können Sie Korrekturen an den Anmeldungen vornehmen oder zukünftige Anmeldungen schneller ausfüllen.</p>',
                    $this->router->generate('fos_user_registration_register')
                );
            }
            if ($this->featureNewsletter) {
                $repositoryNewsletter = $this->getDoctrine()->getRepository(NewsletterSubscription::class);
                if (!$repositoryNewsletter->findOneByEmail($participation->getEmail())) {
                    $message .= sprintf(
                        '<p>Sie können jetzt den <a href="%s">Newsletter abonnieren</a>, um auch in Zukunft von unseren Aktionen erfahren.</p>',
                        $this->router->generate('newsletter_subscription')
                    );
                }
            }

            $this->addFlash(
                'success',
                $message
            );

            return $this->redirectToRoute('event_public_detail', ['eid' => $eid]);
        } else {
            if ($participation->getAddressStreetNumber() === null) {
                $this->addFlash(
                    'warning',
                    sprintf(
                        'Es wurde keine Hausnummer erkannt. Falls Ihre Eingabe <code>%s</code> für Straße <b>und</b> Hausnummer unvollständig ist, <a href="%s#participation-address">vervollständigen Sie bitte die Angabe</a>.',
                        htmlentities($participation->getAddressStreet()),
                        $this->router->generate('event_public_participate', ['eid' => $eid])
                    )
                );
            }
            $this->addWaitingListFlashIfRequired($event);
            return $this->render(
                'event/participation/public/confirm.html.twig',
                [
                    'participation' => $participation,
                    'event'         => $event,
                ]
            );
        }
    }
}
