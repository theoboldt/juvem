<?php

namespace AppBundle\Manager;

use AppBundle\Twig\MailGenerator;
use Swift_Mailer;
use Symfony\Component\Templating\EngineInterface;

abstract class AbstractMailerAwareManager
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