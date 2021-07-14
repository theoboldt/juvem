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


use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\Exception\AuthenticationFailedException;
use Ddeboer\Imap\Exception\ResourceCheckFailureException;
use Ddeboer\Imap\Mailbox;
use Ddeboer\Imap\Server;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swift_Mime_SimpleMessage;
use Symfony\Contracts\Cache\CacheInterface;

class MailImapService
{
    private bool $imapConnectionTried = false;
    
    private ?ConnectionInterface $imapConnection = null;
    
    /**
     * @var null|Mailbox[]
     */
    private ?array $mailboxes = null;
    
    /**
     * Mail Listing cache
     *
     * @var CacheInterface
     */
    private CacheInterface $cache;
    
    /**
     * @var MailConfigurationProvider
     */
    private MailConfigurationProvider $mailConfigurationProvider;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * Initiate a participation manager service
     *
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        MailConfigurationProvider $mailConfigurationProvider,
        CacheInterface $cacheAppEmail,
        LoggerInterface $logger = null
    )
    {
        $this->cache                     = $cacheAppEmail;
        $this->logger                    = $logger ?? new NullLogger();
        $this->mailConfigurationProvider = $mailConfigurationProvider;
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
        $server    = new Server($this->mailConfigurationProvider->getMailerImapHost());
        try {
            $this->imapConnection = $server->authenticate(
                $this->mailConfigurationProvider->getMailerUser(), $this->mailConfigurationProvider->getMailerPassword()
            );
        } catch (AuthenticationFailedException $e) {
            $this->logger->error(
                'IMAP authentication for {user} on {host} failed, exception {class} with message {message}: {trace}',
                [
                    'user'    => $this->mailConfigurationProvider->getMailerUser(),
                    'host'    => $this->mailConfigurationProvider->getMailerImapHost(),
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
                    'user'    => $this->mailConfigurationProvider->getMailerUser(),
                    'host'    => $this->mailConfigurationProvider->getMailerImapHost(),
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
                    'user'    => $this->mailConfigurationProvider->getMailerUser(),
                    'host'    => $this->mailConfigurationProvider->getMailerImapHost(),
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
     * Get all mailboxes
     *
     * @return Mailbox[]
     */
    public function getMailboxes(): array
    {
        if (!$this->mailboxes) {
            $this->mailboxes = [];
            
            $connection = $this->getImapConnection();
            if ($connection) {
                $timeBegin       = microtime(true);
                $this->mailboxes = $connection->getMailboxes();
                $this->logger->info(
                    'Fetched {mailboxes} mailboxes in {duration} seconds',
                    [
                        'mailboxes' => count($this->mailboxes),
                        'duration'  => round(microtime(true) - $timeBegin)
                    ]
                );
            }
        }
        return $this->mailboxes;
    }
    
    /**
     * Get sent mailbox if any
     *
     * @return Mailbox|null
     */
    public function getSentMailbox(): ?Mailbox
    {
        foreach ($this->getMailboxes() as $mailbox) {
            // Skip container-only mailboxes
            // @see https://secure.php.net/manual/en/function.imap-getmailboxes.php
            if ($mailbox->getAttributes() & \LATT_NOSELECT) {
                continue;
            }
            $mailboxNames[] = $mailbox->getName();
            if (in_array(mb_strtolower($mailbox->getName()), ['sent', 'gesendete objekte', 'gesendet'])) {
                return $mailbox;
            }
        }
        return null;
    }
    
    /**
     * Add email to mailbox
     *
     * @param Swift_Mime_SimpleMessage $message
     * @param Mailbox $mailbox
     * @return void
     */
    public function addMessageToBox(\Swift_Mime_SimpleMessage $message, Mailbox $mailbox, bool $seen): void
    {
        $result = $mailbox->addMessage($message->toString(), $seen ? '\\Seen' : null, $message->getDate());
        if (!$result) {
            $this->logger->error(
                'Failed to store email {subject} to {recipient}',
                [
                    'subject'   => $message->getSubject(),
                    'recipient' => implode(', ', $message->getTo()),
                ]
            );
        }
        
        $headers = $message->getHeaders();
        if ($headers->has(MailSendService::HEADER_RELATED_ENTITY_TYPE)
            && $headers->has(MailSendService::HEADER_RELATED_ENTITY_TYPE)
        ) {
            $relatedEntityType = $headers->get(MailSendService::HEADER_RELATED_ENTITY_TYPE)->getFieldBody();
            $relatedEntityId   = (int)$headers->get(MailSendService::HEADER_RELATED_ENTITY_ID)->getFieldBody();
            $this->cache->delete(MailListService::getCacheKey($relatedEntityType, $relatedEntityId));
        }
    }
    
}