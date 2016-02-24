<?php

namespace AppBundle\Manager;

use AppBundle\Entity\Event;
use \AppBundle\Entity\Participation as ParticipationEntity;
use AppBundle\Entity\Participant;
use AppBundle\Twig\MailGenerator;
use Swift_Mailer;
use Symfony\Component\Templating\EngineInterface;
use Twig_Environment;

class ParticipationManager
{

    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * @var MailGenerator
     */
    protected $mailGenerator;

    /**
     * Initiate a participation manager service
     *
     * @param Swift_Mailer  $mailer
     * @param MailGenerator $mailGenerator
     */
    public function __construct(Swift_Mailer $mailer, MailGenerator $mailGenerator)
    {
        $this->mailer        = $mailer;
        $this->mailGenerator = $mailGenerator;
    }

    /**
     * Send a participation request email
     *
     * @param ParticipationEntity $participation
     * @param Event               $event
     */
    public function mailParticipationRequested(ParticipationEntity $participation, Event $event)
    {
        $this->mailEventParticipation('participation', $participation, $event);
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
            $template, array(
                         'event'         => $event,
                         'participation' => $participation,
                         'participants'  => $participation->getParticipants()
                     )
        );
        $message->setTo($participation->getEmail());

        $this->mailer->send($message);
    }

    /**
     * @param Swift_Mailer $mailer
     */
    public function setMailer(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param EngineInterface $templating
     */
    public function setTemplating(EngineInterface $templating)
    {
        $this->templating = $templating;
    }
}