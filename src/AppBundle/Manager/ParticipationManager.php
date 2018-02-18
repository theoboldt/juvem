<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager;

use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\ParticipantPaymentEvent;
use AppBundle\Entity\Participation;
use AppBundle\Entity\Participation as ParticipationEntity;
use AppBundle\Entity\User;
use AppBundle\Twig\MailGenerator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ParticipationManager extends AbstractMailerAwareManager
{

    /**
     * EntityManager
     *
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * Initiate a participation manager service
     *
     * @param UrlGeneratorInterface  $urlGenerator
     * @param Swift_Mailer           $mailer
     * @param MailGenerator          $mailGenerator
     * @param LoggerInterface|null   $logger
     * @param EntityManagerInterface $em
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        Swift_Mailer $mailer,
        MailGenerator $mailGenerator,
        LoggerInterface $logger = null,
        EntityManagerInterface $em
    ) {
        $this->em = $em;
        parent::__construct($urlGenerator, $mailer, $mailGenerator, $logger);
    }

    /**
     * Send a participation request email
     *
     * @param ParticipationEntity $participation
     * @param Event               $event
     */
    public function mailParticipationRequested(ParticipationEntity $participation, Event $event)
    {
        if ($event->getIsAutoConfirm()) {
            $template = 'participation-confirm-auto';
        } else {
            $template = 'participation';
        }
        $this->mailEventParticipation($template, $participation, $event);
    }

    /**
     * Send a participation confirmation request email
     *
     * @param ParticipationEntity $participation
     * @param Event               $event
     */
    public function mailParticipationConfirmed(ParticipationEntity $participation, Event $event)
    {
        $this->mailEventParticipation('participation-confirm', $participation, $event);
    }

    /**
     * Send a participation email, containing participation and event information
     *
     * @param string              $template The message template to use
     * @param ParticipationEntity $participation
     * @param Event               $event
     */
    protected function mailEventParticipation($template, ParticipationEntity $participation, Event $event)
    {
        $message = $this->mailGenerator->getMessage(
            $template, [
                         'event'         => $event,
                         'participation' => $participation,
                         'participants'  => $participation->getParticipants()
                     ]
        );
        $message->setTo(
            $participation->getEmail(),
            Participation::fullname($participation->getNameLast(), $participation->getNameFirst())
        );

        $this->mailer->send($message);
    }

    /**
     * Send a participation email, containing participation and event information
     *
     * @param array $data  The custom text for email
     * @param Event $event The event
     */
    public function mailEventParticipants(array $data, Event $event)
    {
        $dataText = [];
        $dataHtml = [];

        $content = null;
        foreach ($data as $area => $content) {
            $content = str_replace('{EVENT_TITLE}', $event->getTitle(), $content);

            $dataText[$area] = strip_tags($content);

            $contentHtml = htmlentities($content);

            if ($area == 'content') {
                $contentHtml = str_replace(["\n\n", "\r\r", "\r\n\r\n"], '</p><p>', $contentHtml);
            }

            $dataHtml[$area] = $contentHtml;
        }
        unset($content);
        $dataHtml['calltoactioncontent'] = null;

        /** @var Participation $participation */
        foreach ($event->getParticipations() as $participation) {
            if ($participation->isConfirmed()) {
                $dataBoth = [
                    'text' => $dataText,
                    'html' => $dataHtml,
                ];

                $contentList = null;
                foreach ($dataBoth as $type => &$contentList) {
                    $content = null;
                    foreach ($contentList as $area => &$content) {
                        $content = str_replace('{PARTICIPATION_SALUTION}', $participation->getSalution(), $content);
                        $content = str_replace('{PARTICIPATION_NAME_LAST}', $participation->getNameLast(), $content);
                    }
                    unset($content);
                }
                unset($contentList);

                $message = $this->mailGenerator->getMessage(
                    'general-raw', $dataBoth
                );
                $message->setTo(
                    $participation->getEmail(),
                    Participant::fullname($participation->getNameLast(), $participation->getNameFirst())
                );

                $this->mailer->send($message);

            }
        }
    }

    /**
     * Send subscription report to given user
     *
     * @param User      $user      User who receives this report
     * @param array     $eventList List generated by @see EventSubscriptionMailCommand::executeParticipantParse(),
     *                             containing an array identified by event id, each element has 2 items, one identified
     *                             by "event" containing the @see Event entity, one by "participants" containing the
     *                             new participants @see Participant of this event
     * @param \DateTime $lastRun   Contains the timestamp of last execution of mailer
     */
    public function mailSubscriptionReport(User $user, array $eventList, \DateTime $lastRun)
    {
        $participantsCount = 0;
        foreach ($eventList as $event) {
            $participantsCount += count($event['participants']);
        }

        $message = $this->mailGenerator->getMessage(
            'event-subscription-report',
            ['user' => $user, 'events' => $eventList, 'participantsCount' => $participantsCount, 'lastRun' => $lastRun]
        );
        $message->setTo(
            $user->getEmail(),
            User::fullname($user->getNameLast(), $user->getNameFirst())
        );
        $this->mailer->send($message);
    }

    /**
     * Add a new participation (request) to database, set price etc
     *
     * @param  Participation $participation New participation containing related @see Event and @see Participant
     * @param  User|null     $user          Related user account if any used
     * @return Participation
     */
    public function receiveParticipationRequest(Participation $participation, User $user = null)
    {
        $em = $this->em;
        /** @var Participant $participant */

        return $em->transactional(
            function (EntityManager $em) use ($participation, $user) {
                $participation->setAssignedUser($user);

                $event = $participation->getEvent();

                if ($event->getIsAutoConfirm()) {
                    $participation->setIsConfirmed(true);
                }

                // replace $participation by managed version
                $participation = $em->merge($participation);
                $em->persist($participation);

                $price = $event->getPrice();
                if ($price) {
                    $paymentEvents = [];
                    /** @var Participant $participant */
                    foreach ($participation->getParticipants() as $participant) {
                        $participant->setPrice($price);
                        $em->persist($participant);
                        $payment = ParticipantPaymentEvent::createPriceSetEvent(
                            null, $price, 'Standard'
                        );
                        $participant->addPaymentEvent($payment);
                        $em->persist($payment);
                    }
                }

                $em->persist($participation);
                $em->flush();
                return $participation;
            }
        );
    }

}