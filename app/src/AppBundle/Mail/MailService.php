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
use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\Exception\AuthenticationFailedException;
use Ddeboer\Imap\Exception\ResourceCheckFailureException;
use Ddeboer\Imap\Mailbox;
use Ddeboer\Imap\Server;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swift_Message;
use Swift_Mime_SimpleMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class MailService implements UrlGeneratorInterface
{
    private string $mailerHost;
    
    private string $mailerUser;
    
    private string $mailerPassword;
    
    private bool $imapConnectionTried = false;
    
    private ?ConnectionInterface $imapConnection = null;
    
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
     * @param string $mailerHost
     * @param string $mailerUser
     * @param string $mailerPassword
     * @param UrlGeneratorInterface $urlGenerator
     * @param \Swift_Mailer $mailer
     * @param MailGenerator $mailGenerator
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        string $mailerHost,
        string $mailerUser,
        string $mailerPassword,
        UrlGeneratorInterface $urlGenerator,
        \Swift_Mailer $mailer,
        MailGenerator $mailGenerator,
        LoggerInterface $logger = null
    )
    {
        $this->mailerHost     = $mailerHost;
        $this->mailerUser     = $mailerUser;
        $this->mailerPassword = $mailerPassword;
        $this->urlGenerator   = $urlGenerator;
        $this->mailer         = $mailer;
        $this->mailGenerator  = $mailGenerator;
        $this->logger         = $logger ?? new NullLogger();
    }
    
    /**
     * Get IMAP connection to configured mail account
     *
     * @return ConnectionInterface|null
     */
    private function getImapConnection(): ?ConnectionInterface
    {
        if ($this->imapConnectionTried) {
            return $this->imapConnection;
        }
        $this->imapConnectionTried = true;
        
        if (!function_exists('imap_open')) {
            $this->logger->warning('PHP IMAP Extension unavailable');
            return null;
        }
        $timeBegin = microtime(true);
        $server    = new Server($this->mailerHost);
        try {
            $this->imapConnection = $server->authenticate($this->mailerUser, $this->mailerPassword);
        } catch (AuthenticationFailedException $e) {
            $this->logger->error(
                'IMAP authentication for {user} on {host} failed, exception {class} with message {message}: {trace}',
                [
                    'user'    => $this->mailerUser,
                    'host'    => $this->mailerHost,
                    'class'   => get_class($e),
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString()
                ]
            );
            return null;
        } catch (ResourceCheckFailureException $e) {
            $this->logger->error(
                'IMAP authentication for {user} on {host} failed, exception {class} with message {message}: {trace}',
                [
                    'user'    => $this->mailerUser,
                    'host'    => $this->mailerHost,
                    'class'   => get_class($e),
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString()
                ]
            );
            return null;
        } catch (\Exception $e) {
            $this->logger->error(
                'IMAP authentication for {user} on {host} failed, generic exception {class} with message {message}: {trace}',
                [
                    'user'    => $this->mailerUser,
                    'host'    => $this->mailerHost,
                    'class'   => get_class($e),
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString()
                ]
            );
        }
        $this->logger->info(
            'Connected to mail in {duration} seconds', ['duration' => round(microtime(true) - $timeBegin)]
        );
        
        return $this->imapConnection;
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
        $connection = $this->getImapConnection();
        if (!$connection) {
            return null;
        }
        $timeBegin = microtime(true);
        $mailboxes = $connection->getMailboxes();
        
        $mailboxNames = [];
        foreach ($mailboxes as $mailbox) {
            // Skip container-only mailboxes
            // @see https://secure.php.net/manual/en/function.imap-getmailboxes.php
            if ($mailbox->getAttributes() & \LATT_NOSELECT) {
                continue;
            }
            $mailboxNames[] = $mailbox->getName();
            if (mb_strtolower($mailbox->getName()) === 'sent') {
                $this->sentMailbox = $mailbox;
            }
        }
        
        if (!$this->sentMailbox) {
            $this->logger->error(
                'Unable to identify sent mailbox, found {mailboxes}', ['mailboxes' => implode(', ', $mailboxNames)]
            );
        }
        $this->logger->info(
            'Found sent mailbox in {duration} seconds', ['duration' => round(microtime(true) - $timeBegin)]
        );
        
        return $this->sentMailbox;
    }
    
    /**
     * @param Swift_Mime_SimpleMessage $message
     * @throws \Exception
     */
    public function send(Swift_Mime_SimpleMessage $message): int
    {
        $messageHeaders = $message->getHeaders();
        $messageHeaders->addTextHeader('X-Application-Sender', 'Juvem');
        
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