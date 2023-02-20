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

use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Controller\AuthorizationAwareControllerTrait;
use AppBundle\Controller\Event\Gallery\GalleryPublicController;
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Controller\RoutingControllerTrait;
use AppBundle\Entity\AcquisitionAttribute\Formula\CalculationImpossibleException;
use AppBundle\Entity\CustomField\CustomFieldValueContainer;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\InvalidTokenHttpException;
use AppBundle\Manager\CommentManager;
use AppBundle\Manager\ParticipationManager;
use AppBundle\Manager\Payment\PaymentManager;
use AppBundle\Twig\Extension\BootstrapGlyph;
use AppBundle\Twig\Extension\CustomFieldValue;
use AppBundle\Twig\Extension\PaymentInformation;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use libphonenumber\PhoneNumberUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

class AdminMultipleController
{
    use RenderingControllerTrait, RoutingControllerTrait, AuthorizationAwareControllerTrait;
    
    /**
     * doctrine
     *
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;
    
    /**
     * security.csrf.token_manager
     *
     * @var CsrfTokenManagerInterface
     */
    private CsrfTokenManagerInterface $csrfTokenManager;
    
    /**
     * app.payment_manager
     *
     * @var PaymentManager
     */
    private PaymentManager $paymentManager;
    
    /**
     * app.participation_manager
     *
     * @var ParticipationManager
     */
    private ParticipationManager $participationManager;
    
    /**
     * app.comment_manager
     *
     * @var CommentManager
     */
    private CommentManager $commentManager;
    
    /**
     * AdminMultipleController constructor.
     *
     * @param Environment $twig
     * @param RouterInterface $router
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ManagerRegistry $doctrine
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param PaymentManager $paymentManager
     * @param ParticipationManager $participationManager
     * @param CommentManager $commentManager
     */
    public function __construct(
        Environment $twig,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker,
        ManagerRegistry $doctrine,
        CsrfTokenManagerInterface $csrfTokenManager,
        PaymentManager $paymentManager,
        ParticipationManager $participationManager,
        CommentManager $commentManager
    )
    {
        $this->twig                 = $twig;
        $this->router               = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrine             = $doctrine;
        $this->csrfTokenManager     = $csrfTokenManager;
        $this->paymentManager       = $paymentManager;
        $this->participationManager = $participationManager;
        $this->commentManager       = $commentManager;
    }
    
    
    /**
     * Page for list of participants of an event
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants", requirements={"eid": "\d+"}, name="event_participants_list")
     * @Security("is_granted('participants_read', event)")
     */
    public function listParticipantsAction(Event $event)
    {
        return $this->render('event/participation/admin/participants-list.html.twig', array('event' => $event));
    }

    /**
     * Page for list of participants of an event having a provided age at a specific date
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants-age", requirements={"eid": "\d+"},
     *                                               name="event_participants_list_specific_age")
     * @Security("is_granted('participants_read', event)")
     */
    public function listParticipantsWithSpecificAgeAction(Event $event)
    {
        return $this->render(
            'event/participation/admin/participants-list-specific-age.html.twig', ['event' => $event]
        );
    }

    /**
     * Data provider for events participants list grid having specific age at specific date
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants-specific-age.json", requirements={"eid": "\d+"},
     *                                                             name="event_participants_list_specific_age_data")
     * @Security("is_granted('participants_read', event)")
     */
    public function listParticipantsWithSpecificAgeDataAction(Event $event, Request $request)
    {
        if (!$event->getSpecificDate()) {
            return new JsonResponse([]);
        }

        $participationRepository = $this->doctrine->getRepository(Participation::class);
        $participantEntityList   = $participationRepository->participantsList($event, null, true, true);

        $participantList = [];

        /** @var Participant $participant */
        foreach ($participantEntityList as $participant) {

            $yearsOfLife = EventRepository::yearsOfLife($participant->getBirthday(), $event->getSpecificDate());
            if ($yearsOfLife < $event->getSpecificAge()) {
                continue;
            }

            $age = $yearsOfLife;
            $age .= ' <span class="rounded-age">('
                    . number_format(EventRepository::age($participant->getBirthday(), $event->getSpecificDate()), 1, ',', '.')
                    . ')</span>';

            $birthday = $participant->getBirthday()->format('d.m.Y');
            if (EventRepository::hasBirthdayInTimespan($participant->getBirthday(), $event->getSpecificDate())) {
                $glyph    = new BootstrapGlyph();
                $birthday .= ' ' . $glyph->bootstrapGlyph('gift', 'Hat am Stichtag Geburtstag');
            }
            $participantEntry = [
                'aid'       => $participant->getAid(),
                'pid'       => $participant->getParticipation()->getPid(),
                'nameFirst' => $participant->getNameFirst(),
                'nameLast'  => $participant->getNameLast(),
                'age'       => $age,
                'birthday'  => $birthday,
                'gender'    => $participant->getGender(),
            ];

            $participantList[] = $participantEntry;
        }

        return new JsonResponse($participantList);
    }

    /**
     * Navigate to other participation
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("expectedParticipation", class="AppBundle:Participation", options={"id" = "pid"})
     * @Route("/admin/event/{eid}/participation/{pid}/{direction}", requirements={"eid": "\d+", "pid": "\d+",
     *                                                              "direction":"previous|next"},
     *                                                              name="admin_participation_navigate")
     * @param Event $event
     * @param Participation $expectedParticipation
     * @param string $direction Either previous or next
     * @return Response
     * @Security("is_granted('participants_read', event)")
     */
    public function navigateParticipation(Event $event, Participation $expectedParticipation, string $direction)
    {
        $participationTarget = $this->findNextParticipation($event, $expectedParticipation, $direction, false);
        if (!$participationTarget) {
            $participationTarget = $this->findNextParticipation($event, $expectedParticipation, $direction, true);
            if (!$participationTarget) {
                throw new NotFoundHttpException('Failed to identify desired participation');
            }
        }
        
        return $this->redirectToRoute(
            'event_participation_detail', ['eid' => $event->getEid(), 'pid' => $participationTarget->getPid()]
        );
    }
    
    /**
     * Try to find next participation
     *
     * @param Event $event
     * @param Participation $expectedParticipation
     * @param string $direction
     * @param bool $includeDeletedAndWithdrawn
     * @return Participation|null
     */
    private function findNextParticipation(
        Event $event, Participation $expectedParticipation, string $direction, bool $includeDeletedAndWithdrawn
    ): ?Participation
    {
        $participationRepository = $this->doctrine->getRepository(Participation::class);
        $participants            = $participationRepository->participantsList(
            $event, null, $includeDeletedAndWithdrawn, $includeDeletedAndWithdrawn
        );
        $participations          = [];

        /** @var Participant $participant */ //prepare ordered list
        foreach ($participants as $participant) {
            $participation                            = $participant->getParticipation();
            $participations[$participation->getPid()] = $participation;
        }
        $participations      = array_values($participations); //get rid of indexes, keep order
        $participationTarget = null;
        if (!count($participations)) {
            throw new NotFoundHttpException('Participations list seems to be empty');
        } elseif (count($participations) === 1) {
            $participationTarget = reset($participations);
        } else {
            foreach ($participations as $index => $participation) {
                if ($participation->getPid() === $expectedParticipation->getPid()) {
                    //found
                    if ($direction === 'previous') {
                        if (isset($participations[$index - 1])) {
                            $participationTarget = $participations[$index - 1];
                        } else {
                            $participationTarget = end($participations);
                        }
                    } else {
                        if (isset($participations[$index + 1])) {
                            $participationTarget = $participations[$index + 1];
                        } else {
                            $participationTarget = reset($participations);
                        }
                    }
                    break;
                }
            }
        }
        return $participationTarget;
    }

    /**
     * Data provider for events participants list grid
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participants.json", requirements={"eid": "\d+"}, name="event_participants_list_data")
     * @Security("is_granted('participants_read', event)")
     */
    public function listParticipantsDataAction(Event $event, Request $request)
    {
        $includePayment          = ($request->query->has('payment'));
        $participationRepository = $this->doctrine->getRepository(Participation::class);
        $participantEntityList   = $participationRepository->participantsList($event, null, true, true);
        $paymentManager          = $this->paymentManager;

        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $statusFormatter = ParticipantStatus::formatter();
        
        $customFieldValueExtension = $this->twig->getExtension(CustomFieldValue::class);
        if (!$customFieldValueExtension instanceof CustomFieldValue) {
            throw new \RuntimeException('Need to fetch '.CustomFieldValue::class.' from twig');
        }

        $participantList = array();
        /** @var Participant $participant */
        foreach ($participantEntityList as $participant) {
            $participation        = $participant->getParticipation();
            $participationDate    = $participation->getCreatedAt();
            $participantPhoneList = array();

            /** @var PhoneNumber $phoneNumberEntity */
            foreach ($participation->getPhoneNumbers()
                                   ->getIterator() as $phoneNumberEntity) {
                /** @var \libphonenumber\PhoneNumber $phoneNumber */
                $phoneNumber            = $phoneNumberEntity->getNumber();
                $participantPhoneList[] = $phoneNumberUtil->formatOutOfCountryCallingNumber($phoneNumber, 'DE');
            }

            if ($includePayment) {
                $paymentStatus = $paymentManager->getParticipantPaymentStatus($participant);
            } else {
                $paymentStatus = null;
            }

            $participantAction = '';

            $participantStatus = $participant->getStatus(true);

            $age = '';
            if ($participant->hasBirthdayAtEvent()) {
                $age = '<span class="birthday-during-event">';
            }
            if ($participant->getYearsOfLifeAtEvent() !== null) {
                $age .= '<span class="years-of-life">' . $participant->getYearsOfLifeAtEvent() . '</span>';
                $age .= ' <span class="rounded-age">(' . number_format($participant->getAgeAtEvent(), 1, ',', '.') . ')</span>';
                if ($participant->hasBirthdayAtEvent()) {
                    '</span>';
                }
            }
            $participantStatusText = $statusFormatter->formatMask($participantStatus);
            if ($participant->isDeleted()) {
                $participantStatusText .= ' <span class="label label-danger">gelöscht</span>';
            }
            $paymentImpossible = false;
            if ($paymentStatus) {
                try {
                    $participantStatusText .= ' ' . PaymentInformation::provideLabel($paymentStatus);
                } catch (CalculationImpossibleException $e) {
                    $paymentImpossible     = true;
                    $participantStatusText .= ' <span class="label label-warning" title="Der Preis des Teilnehmenden kann nicht berechnet werden. Die Variablen sollten geprüft werden.">Preis unbekannt</span>';
                }
            }
            $participantStatusWithdrawn = $participantStatus->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
            $participantStatusRejected  = $participantStatus->has(ParticipantStatus::TYPE_STATUS_REJECTED);

            $basePrice = $participant->getBasePrice(true);

            $participantEntry = [
                'aid'                      => $participant->getAid(),
                'pid'                      => $participant->getParticipation()->getPid(),
                'is_paid'                  => null, //for tri state filter
                'is_deleted'               => (int)($participant->getDeletedAt() instanceof DateTime),
                'is_withdrawn'             => (int)$participantStatusWithdrawn,
                'is_rejected'              => (int)$participantStatusRejected,
                'is_withdrawn_or_rejected' => (int)($participantStatusWithdrawn || $participantStatusRejected),
                'is_confirmed'             => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_CONFIRMED),
                'payment_base_price'       => $basePrice === null
                    ? '<i>keiner</i>'
                    : number_format($basePrice, 2, ',', '.') . '&nbsp;€',
                'nameFirst'                => $participant->getNameFirst(),
                'nameLast'                 => $participant->getNameLast(),
                'age'                      => $age,
                'phone'                    => implode(', ', $participantPhoneList),
                'status'                   => $participantStatusText,
                'gender'                   => $participant->getGender(),
                'registrationDate'         => $participationDate->format(Event::DATE_FORMAT_DATE_TIME),
                'action'                   => $participantAction,
            ];

            if ($includePayment) {
                try {
                    $price = $paymentStatus->getPrice(true);

                    if ($paymentStatus->isInactive()) {
                        $toPay = '';
                        if ($paymentStatus->getPaymentSum(false) < 0) {
                            $toPay = number_format($paymentStatus->getPaymentSum(true), 2, ',', '.') . '&nbsp;€';
                        }
                        $toPay .= ' <i title="Keine Zahlung nötig da inaktiv">inaktiv</i>';

                        $participantEntry['payment_to_pay'] = $toPay;
                    } else {
                        $toPay                              = $paymentStatus->getToPayValue(true);
                        $participantEntry['payment_to_pay'] = $paymentStatus->isFree()
                            ? '<i>nichts</i>'
                            : number_format($toPay, 2, ',', '.') . '&nbsp;€';
                    }
                    $paymentPrice = $paymentStatus->hasPriceSet()
                        ? number_format($price, 2, ',', '.') . '&nbsp;€'
                        : '<i>keiner</i>';
                    if ($paymentStatus->isInactive()) {
                        $paymentPrice = '<i>' . $paymentPrice . '</i>';
                    }

                    $participantEntry['is_paid'] = (int)($paymentStatus->isPaid());

                } catch (CalculationImpossibleException $e) {
                    $paymentImpossible = true;
                }

                if ($paymentImpossible) {
                    $paymentPrice
                        = '<span class="label label-danger" title="Der Preis des Teilnehmenden kann nicht berechnet werden. Die Variablen sollten geprüft werden.">unbekannt</span>';
                    
                    $participantEntry['payment_to_pay'] = $paymentPrice;
                }

                $participantEntry['payment_price'] = $paymentPrice;
            }
            
            /** @var CustomFieldValueContainer $customFieldValueContainer */
            foreach ($participation->getCustomFieldValues() as $customFieldValueContainer) {
                $participantEntry['participation_custom_field_' . $customFieldValueContainer->getCustomFieldId()]
                    = $customFieldValueExtension->customFieldValue($this->twig, $customFieldValueContainer, $participation, false);
            }
            /** @var CustomFieldValueContainer $customFieldValueContainer */
            foreach ($participant->getCustomFieldValues() as $customFieldValueContainer) {
                $participantEntry['participant_custom_field_' . $customFieldValueContainer->getCustomFieldId()]
                    = $customFieldValueExtension->customFieldValue($this->twig, $customFieldValueContainer, $participant, false);
            }
            $participantList[] = $participantEntry;
        }

        $response = new JsonResponse($participantList);
        $response->setEtag(sha1(json_encode($participantList)));

        return $response;
    }

    /**
     * Apply changes to multiple participants
     *
     * @CloseSessionEarly
     * @Route("/admin/event/participantschange", name="event_participants_change", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function activeButtonChangeStateHandler(Request $request)
    {
        $token        = $request->get('_token');
        $eid          = $request->get('eid');
        $action       = $request->get('action');
        $message      = $request->get('message');
        $participants = filter_var_array($request->get('participants'), FILTER_VALIDATE_INT);

        /** @var CsrfTokenManagerInterface $csrf */
        $csrf = $this->csrfTokenManager;
        if ($token != $csrf->getToken('participants-list-edit' . $eid)) {
            throw new InvalidTokenHttpException();
        }

        $eventRepository         = $this->doctrine->getRepository(Event::class);
        $participationRepository = $this->doctrine->getRepository(Participation::class);
        /** @var Event $event */
        $event                   = $eventRepository->findOneBy(['eid' => $eid]);
        $this->denyAccessUnlessGranted('participants_edit', $event);
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', ['eid' => $eid],
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $participants         = $participationRepository->participantsList($event, $participants, true, true);
        $participationManager = $this->participationManager;
        $paymentManager       = $this->paymentManager;
        $em                   = $this->doctrine->getManager();

        /** @var Participant $participant */
        foreach ($participants as $participant) {
            $participation = $participant->getParticipation();
            $changed = false;

            switch ($action) {
                case 'confirm':
                    if (!$participation->isConfirmed()) {
                        $participation->setIsConfirmed(true);
                        $participationManager->mailParticipationConfirmed($participation, $participation->getEvent());
                        $changed = true;
                    }
                    break;
                case 'paid':
                    $toPay = $paymentManager->getToPayValueForParticipant($participant, false);
                    if ($toPay > 0) {
                        $paymentManager->handlePaymentForParticipant($participant, ($toPay*-1), $message);
                    }
                    break;
            }
            if ($changed) {
                $em->persist($participation);
            }
        }
        $em->flush();

        return new JsonResponse();
    }

    /**
     * Page for list of participants of an event
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/participate-timeline.json", requirements={"eid": "\d+"},
     *                                                        name="event_participate_timeline")
     * @Security("is_granted('read', event)")
     */
    public function provideParticipateTimelineDataAction(Event $event)
    {
        $participationRepository = $this->doctrine->getRepository(Participation::class);
        $newParticipants = [];
        /** @var Participant $participant */
        foreach ($participationRepository->participantsList($event) as $participant) {
            $date = $participant->getCreatedAt()->format('Y-m-d');
            if (!isset($newParticipants[$date])) {
                $newParticipants[$date] = 0;
            }
            $newParticipants[$date] += 1;
        }

        ksort($newParticipants);
        reset($newParticipants);
        $startDate   = new DateTime(key($newParticipants) . ' 10:00:00');
        $currentDate = clone $startDate;

        $endDate = clone $event->getStartDate();
        $endDate->setTime(10, 0);

        $today = new DateTime();
        $today->setTime(10, 0);

        $days = 0;
        $countBefore = 0;
        $countCurrent = 0;
        $history     = [];
        while($currentDate <= $endDate && $currentDate < $today) {
            $countCurrent = $countBefore;

            $date = $currentDate->format('Y-m-d');
            $diff = $currentDate->diff($endDate);

            if (isset($newParticipants[$date])) {
                $countCurrent += $newParticipants[$date];
            }

            $year  = $currentDate->format('Y');
            $month = GalleryPublicController::convertMonthNumber((int)$currentDate->format('n'));
            $day   = $currentDate->format('d');
            if (!isset($history[$year])) {
                $history[$year] = [];
            }
            if (!isset($history[$year][$month])) {
                $history[$year][$month] = [];
            }
            $history[$year][$month][] = [
                'day'   => $day,
                'count' => $countCurrent,
                'days'  => $diff->days,
            ];


            $countBefore = $countCurrent;
            $currentDate->modify('+1 day');
            ++$days;
        }

        return new JsonResponse(['history' => $history, 'participantsTotal' => $countCurrent, 'daysTotal' => $days]);
    }
}
