<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Mail;


use AppBundle\Twig\MailGenerator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swift_Message;
use Swift_Mime_SimpleMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class MailSendService implements UrlGeneratorInterface
{
    const HEADER_APPLICATION = 'X-Application';
    
    const HEADER_APPLICATION_SENDER = 'X-Application-Sender';
    
    const HEADER_ORGANIZATION = 'X-Organization';
    
    const HEADER_RELATED_ENTITY = 'X-Related-Entity';
    
    const HEADER_RELATED_ENTITY_TYPE = 'X-Related-Type';
    
    const HEADER_RELATED_ENTITY_ID = 'X-Related-Id';
    
    private MailImapService $mailImapService;
    
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;
    
    /**
     * @var MailGenerator
     */
    protected $mailGenerator;
    
    /**
     * Router used to create the routes for the transmitted pages
     *
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;
    
    /**
     * @var MailConfigurationProvider
     */
    private MailConfigurationProvider $mailConfigurationProvider;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     * Initiate a participation manager service
     *
     * @param MailConfigurationProvider $mailConfigurationProvider
     * @param UrlGeneratorInterface $urlGenerator
     * @param \Swift_Mailer $mailer
     * @param MailGenerator $mailGenerator
     * @param MailImapService $mailImapService
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        MailConfigurationProvider $mailConfigurationProvider,
        UrlGeneratorInterface $urlGenerator,
        \Swift_Mailer $mailer,
        MailGenerator $mailGenerator,
        MailImapService $mailImapService,
        LoggerInterface $logger = null
    )
    {
        $this->urlGenerator              = $urlGenerator;
        $this->mailer                    = $mailer;
        $this->mailGenerator             = $mailGenerator;
        $this->logger                    = $logger ?? new NullLogger();
        $this->mailImapService           = $mailImapService;
        $this->mailConfigurationProvider = $mailConfigurationProvider;
    }
    
    
    /**
     * @param Swift_Mime_SimpleMessage $message
     * @throws \Exception
     */
    public function send(Swift_Mime_SimpleMessage $message): int
    {
        $message->setSender(
            $this->mailConfigurationProvider->getMailerAddress(), $this->mailConfigurationProvider->organizationName()
        );
        $message->setFrom(
            $this->mailConfigurationProvider->getMailerAddress(), $this->mailConfigurationProvider->organizationName()
        );
        if ($this->mailConfigurationProvider->organizationEmail()
            !== $this->mailConfigurationProvider->getMailerAddress()
        ) {
            $message->setReplyTo(
                $this->mailConfigurationProvider->organizationEmail(),
                $this->mailConfigurationProvider->organizationName()
            );
        }
        $messageHeaders = $message->getHeaders();
        $messageHeaders->addTextHeader(self::HEADER_APPLICATION_SENDER, 'Juvem');
        
        $sent = 0;
        try {
            $sent = $this->mailer->send($message, $failedRecipients);
        } catch (\Exception $e) {
            $this->logger->error('Exception while sending mail: {message}', ['message' => $e->getMessage()]);
            throw $e;
        }
        
        if (count($failedRecipients)) {
            $this->logger->error(
                'Failed to email to recipients: {recipients}', ['recipients' => implode(', ', $failedRecipients)]
            );
        }
        
        $sentMailbox = $this->mailImapService->getSentMailbox();
        if ($sentMailbox) {
            $this->mailImapService->addMessageToBox(
                $message,
                $sentMailbox,
                true
            );
        }
        
        return $sent;
    }
    
    
    /**
     * Create a swift message by using identifier
     *
     * @param string $template  Twig template identifier
     * @param array $parameters Parameters for template
     * @return Swift_Message      The email
     */
    public function getTemplatedMessage(string $template, array $parameters = []): Swift_Message
    {
        return $this->mailGenerator->getMessage($template, $parameters);
    }
    
    /**
     * {@inheritDoc}
     */
    public function setContext(RequestContext $context)
    {
        return $this->urlGenerator->setContext($context);
    }
    
    /**
     * {@inheritDoc}
     */
    public function getContext()
    {
        return $this->urlGenerator->getContext();
    }
    
    /**
     * {@inheritDoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->urlGenerator->generate($name, $parameters, $referenceType);
    }
    
    /**
     * Add related entity header to message
     *
     * @param Swift_Message $message
     * @param string $class
     * @param int|null $id
     */
    public static function addRelatedEntityMessageHeader(\Swift_Message $message, string $class, ?int $id = null)
    {
        $headers = $message->getHeaders();
        $value   = $class;
        if ($id) {
            $value .= ':' . $id;
        }
        
        $headers->addTextHeader(MailSendService::HEADER_RELATED_ENTITY, $value);
    }
}