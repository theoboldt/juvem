<?php

namespace AppBundle\Manager;

use AppBundle\Entity\Event;
use \AppBundle\Entity\Participation as ParticipationEntity;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Twig\MailGenerator;
use Swift_Mailer;
use Symfony\Component\Templating\EngineInterface;
use Twig_Environment;

class ParticipationExportManager
{

    /**
     * Initiate a participation manager service
     */
    public function __construct()
    {
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