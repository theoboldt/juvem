<?php

namespace AppBundle\Manager;

use AppBundle\Twig\MailGenerator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Initiate a participation manager service
     *
     * @param Swift_Mailer         $mailer
     * @param MailGenerator        $mailGenerator
     * @param LoggerInterface|null $logger
     */
    public function __construct(Swift_Mailer $mailer, MailGenerator $mailGenerator, LoggerInterface $logger = null)
    {
        if (!$logger) {
            $logger = new NullLogger();
        }
        $this->mailer        = $mailer;
        $this->mailGenerator = $mailGenerator;
        $this->logger        = $logger;
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