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


    public function __construct(Swift_Mailer $mailer, MailGenerator $mailGenerator)
    {
        $this->mailer        = $mailer;
        $this->mailGenerator = $mailGenerator;
    }

    public function mailParticipationRequested(ParticipationEntity $participation, Event $event)
    {

        $message = $this->mailGenerator->getMessage(
            'participation', array(
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