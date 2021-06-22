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

use AppBundle\Entity\Employee;
use AppBundle\Entity\EmployeeComment;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\ParticipantPaymentEvent;
use AppBundle\Entity\Participation;
use AppBundle\Entity\Participation as ParticipationEntity;
use AppBundle\Entity\ParticipationComment;
use AppBundle\Entity\User;
use AppBundle\Form\EventMailType;
use AppBundle\Form\MoveEmployeeType;
use AppBundle\Form\MoveParticipationType;
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
        ?LoggerInterface $logger,
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
        $now = new \DateTime('now');
        $this->mailEventParticipation('participation-confirm', $participation, $event);
        $this->em->transactional(
            function (EntityManager $em) use ($participation, $now) {
                /** @var Participant $participant */
                foreach ($participation->getParticipants() as $participant) {
                    $participant->setConfirmationSentAt($now);
                }
                
                $em->persist($participation);
                $em->flush();
            }
        );
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
                         'participants'  => $participation->getParticipants(),
                     ]
        );
        $message->setTo(
            $participation->getEmail(),
            $participation->fullname()
        );

        $this->mailer->send($message);
    }

    /**
     * Send a participation email, containing participation and event information
     *
     * @param array $data      The custom text for email
     * @param Event $event     The event
     * @param int   $recipient Code for recipient, either \AppBundle\Form\EventMailType::RECIPIENT_ALL,
     *                         \AppBundle\Form\EventMailType::RECIPIENT_CONFIRMED,
     *                         \AppBundle\Form\EventMailType::RECIPIENT_UNNFIRMED
     */
    public function mailEventParticipants(array $data, Event $event, int $recipient)
    {
        $dataText = [];
        $dataHtml = [];

        $dataText = array();
        $dataHtml = array();

        $content = null;
        foreach ($data as $area => $content) {
            $dataText[$area] = strip_tags($content);
            $dataHtml[$area] = $content;
        }
        unset($content);
        $dataHtml['calltoactioncontent'] = null;

        /** @var Participation $participation */
        foreach ($event->getParticipations() as $participation) {
            if ($participation->isRejected()
                || $participation->isWithdrawn()
                || $participation->getDeletedAt() !== null
                || ($participation->isConfirmed() && $recipient === EventMailType::RECIPIENT_UNCONFIRMED)
                || (!$participation->isConfirmed() && $recipient === EventMailType::RECIPIENT_CONFIRMED)
            ) {
                continue;
            }
            $dataBoth = [
                'text' => $dataText,
                'html' => $dataHtml,
            ];

            $contentList = null;
            foreach ($dataBoth as $type => &$contentList) {
                $content = null;
                foreach ($contentList as $area => &$content) {
                    $content = str_replace('{PARTICIPATION_SALUTATION}', $participation->getSalutation(), $content);
                    $content = str_replace('{PARTICIPATION_NAME_LAST}', $participation->getNameLast(), $content);
                }
                unset($content);
            }
            unset($contentList);

            $message = $this->mailGenerator->getMessage('general-markdown', $dataBoth);

            $message->setTo(
                $participation->getEmail(),
                $participation->fullname()
            );

            $this->mailer->send($message);
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
            $user->fullname()
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
                        $participant->setBasePrice($price);
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
    
    /**
     * Perform move @see Participation action, which is actually creating a duplicate and adding some comments
     *
     * @param Participation $oldParticipation The @see Participation to copy
     * @param Event $newEvent                 Target event
     * @param string $commentOldContent       Comment to add at old $oldParticipation
     * @param string $commentNewContent       Comment to add at new participation
     * @param User|null $responsibleUser      User performing this action
     * @return Participation The new created entry
     */
    public function moveParticipation(
        Participation $oldParticipation,
        Event $newEvent,
        string $commentOldContent,
        string $commentNewContent,
        User $responsibleUser = null
    ): Participation
    {
        $newParticipation = Participation::createFromTemplateForEvent($oldParticipation, $newEvent, true);

        return $this->em->transactional(
            function (EntityManager $em) use ($oldParticipation, $newParticipation, $commentOldContent, $commentNewContent, $responsibleUser) {

                /** @var Participant $participant */
                foreach ($oldParticipation->getParticipants() as $participant) {
                    $participant->setIsWithdrawn(true);
                    $em->persist($participant);
                }
                
                $em->persist($newParticipation);
                $em->flush();
                
                $updateComment = function($comment) use ($oldParticipation, $newParticipation) {
                    $comment = str_replace(MoveParticipationType::PARAM_EVENT_OLD, $oldParticipation->getEvent()->getTitle(), $comment);
                    $comment = str_replace(MoveParticipationType::PARAM_EVENT_NEW, $newParticipation->getEvent()->getTitle(), $comment);
                    $comment = str_replace(MoveParticipationType::PARAM_PID_OLD, $oldParticipation->getId(), $comment);
                    $comment = str_replace(MoveParticipationType::PARAM_PID_NEW, $newParticipation->getId(), $comment);
                    return $comment;
                };
                
                $commentOldContent = $updateComment($commentOldContent);
                $commentNewContent = $updateComment($commentNewContent);
                
                $commentOld = new ParticipationComment();
                $commentOld->setParticipation($oldParticipation);
                $commentOld->setCreatedAtNow();
                $commentOld->setCreatedBy($responsibleUser);
                $commentOld->setContent($commentOldContent);
                $em->persist($commentOld);

                $commentNew = new ParticipationComment();
                $commentNew->setParticipation($newParticipation);
                $commentNew->setCreatedAtNow();
                $commentNew->setCreatedBy($responsibleUser);
                $commentNew->setContent($commentNewContent);
                $em->persist($commentNew);
                $em->flush();
                
                return $newParticipation;
            }
        );
    }
    
    /**
     * Perform move @see Employee action, which is actually creating a duplicate and adding some comments
     *
     * @param Employee $oldEmployee The @see Employee to copy
     * @param Event $newEvent                 Target event
     * @param string $commentOldContent       Comment to add at old $oldEmployee
     * @param string $commentNewContent       Comment to add at new employee
     * @param User|null $responsibleUser      User performing this action
     * @return Employee The new created entry
     */
    public function moveEmployee(
        Employee $oldEmployee,
        Event $newEvent,
        string $commentOldContent,
        string $commentNewContent,
        User $responsibleUser = null
    ): Employee
    {
        $newEmployee = Employee::createFromTemplateForEvent($oldEmployee, $newEvent, true);

        return $this->em->transactional(
            function (EntityManager $em) use ($oldEmployee, $newEmployee, $commentOldContent, $commentNewContent, $responsibleUser) {

                $oldEmployee->setDeletedAt(new \DateTime());
                $em->persist($oldEmployee);
                $em->persist($newEmployee);
                $em->flush();
                
                $updateComment = function($comment) use ($oldEmployee, $newEmployee) {
                    $comment = str_replace(MoveEmployeeType::PARAM_EVENT_OLD, $oldEmployee->getEvent()->getTitle(), $comment);
                    $comment = str_replace(MoveEmployeeType::PARAM_EVENT_NEW, $newEmployee->getEvent()->getTitle(), $comment);
                    $comment = str_replace(MoveEmployeeType::PARAM_PID_OLD, $oldEmployee->getId(), $comment);
                    $comment = str_replace(MoveEmployeeType::PARAM_PID_NEW, $newEmployee->getId(), $comment);
                    return $comment;
                };
                
                $commentOldContent = $updateComment($commentOldContent);
                $commentNewContent = $updateComment($commentNewContent);
                
                $commentOld = new EmployeeComment();
                $commentOld->setEmployee($oldEmployee);
                $commentOld->setCreatedAtNow();
                $commentOld->setCreatedBy($responsibleUser);
                $commentOld->setContent($commentOldContent);
                $em->persist($commentOld);

                $commentNew = new EmployeeComment();
                $commentNew->setEmployee($newEmployee);
                $commentNew->setCreatedAtNow();
                $commentNew->setCreatedBy($responsibleUser);
                $commentNew->setContent($commentNewContent);
                $em->persist($commentNew);
                $em->flush();
                
                return $newEmployee;
            }
        );
    }
}