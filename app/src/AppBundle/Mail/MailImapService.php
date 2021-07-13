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

class MailImapService
{
    private string $mailerImapHost;
    
    private string $mailerUser;
    
    private string $mailerPassword;
    
    private bool $imapConnectionTried = false;
    
    private ?ConnectionInterface $imapConnection = null;
    
    /**
     * @var null|Mailbox[]
     */
    private ?array $mailboxes = null;
    
    /**
     * Initiate a participation manager service
     *
     * @param string $mailerHost
     * @param string|null $mailerImapHost
     * @param string $mailerUser
     * @param string $mailerPassword
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        string $mailerHost,
        ?string $mailerImapHost,
        string $mailerUser,
        string $mailerPassword,
        LoggerInterface $logger = null
    )
    {
        $this->mailerImapHost = $mailerImapHost ?: $mailerHost;
        $this->mailerUser     = $mailerUser;
        $this->mailerPassword = $mailerPassword;
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
        $server    = new Server($this->mailerImapHost);
        try {
            $this->imapConnection = $server->authenticate($this->mailerUser, $this->mailerPassword);
        } catch (AuthenticationFailedException $e) {
            $this->logger->error(
                'IMAP authentication for {user} on {host} failed, exception {class} with message {message}: {trace}',
                [
                    'user'    => $this->mailerUser,
                    'host'    => $this->mailerImapHost,
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
                    'host'    => $this->mailerImapHost,
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
                    'host'    => $this->mailerImapHost,
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
    
}