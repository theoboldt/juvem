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
use Ddeboer\Imap\Mailbox;
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
    
    const HEADER_RELATED_ENTITY_TYPE = 'X-Related-Type';
    
    const HEADER_RELATED_ENTITY_ID = 'X-Related-Id';
    
    private MailImapService $mailImapService;
    
    private ?Mailbox $sentMailbox = null;
    
    /**
     * @var \Swift_Mailer
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
     * Router used to create the routes for the transmitted pages
     *
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;
    
    /**
     * Initiate a participation manager service
     *
     * @param UrlGeneratorInterface $urlGenerator
     * @param \Swift_Mailer $mailer
     * @param MailGenerator $mailGenerator
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        \Swift_Mailer $mailer,
        MailGenerator $mailGenerator,
        MailImapService $mailImapService,
        LoggerInterface $logger = null
    )
    {
        $this->urlGenerator   = $urlGenerator;
        $this->mailer         = $mailer;
        $this->mailGenerator  = $mailGenerator;
        $this->logger         = $logger ?? new NullLogger();
        $this->mailImapService = $mailImapService;
    }
    
    /**
     * Get sent mailbox if any
     *
     * @return Mailbox|null
     */
    private function getSentMailbox(): ?Mailbox
    {
        if ($this->sentMailbox) {
            return $this->sentMailbox;
        }
        $mailboxes = $this->mailImapService->getMailboxes();
        
        $mailboxNames = [];
        foreach ($mailboxes as $mailbox) {
            // Skip container-only mailboxes
            // @see https://secure.php.net/manual/en/function.imap-getmailboxes.php
            if ($mailbox->getAttributes() & \LATT_NOSELECT) {
                continue;
            }
            $mailboxNames[] = $mailbox->getName();
            if (in_array(mb_strtolower($mailbox->getName()), ['sent', 'gesendete objekte', 'gesendet'])) {
                $this->sentMailbox = $mailbox;
            }
        }
        
        if (!$this->sentMailbox) {
            $this->logger->error(
                'Unable to identify sent mailbox, found {mailboxes}', ['mailboxes' => implode(', ', $mailboxNames)]
            );
        }
        
        return $this->sentMailbox;
    }
    
    /**
     * @param Swift_Mime_SimpleMessage $message
     * @throws \Exception
     */
    public function send(Swift_Mime_SimpleMessage $message): int
    {
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
        $mailbox = $this->getSentMailbox();
        if ($mailbox) {
            $result = $mailbox->addMessage($message->toString(), '\\Seen');
            if (!$result) {
                $this->logger->error('Failed to store sent email');
            }
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
}