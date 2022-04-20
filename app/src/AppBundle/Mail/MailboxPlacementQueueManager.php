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

use Psr\Log\LoggerInterface;

class MailboxPlacementQueueManager
{
    const SERVICE_LOCK_FILE = 'imap_service.lock';

    const SENT_MAILBOX_FOLDER = '_____sent';

    /**
     * @var string
     */
    private string $path;

    /**
     * @var resource|null
     */
    private $imapServiceLockHandle = null;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param string          $path
     * @param LoggerInterface $logger
     */
    public function __construct(string $path, LoggerInterface $logger)
    {
        $this->path        = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->logger      = $logger;
    }

    /**
     * Enqueue mail
     *
     * @param \Swift_Mime_SimpleMessage $message
     * @param string                    $mailboxName
     * @return void
     */
    public function enqueue(\Swift_Mime_SimpleMessage $message, string $mailboxName): void
    {
        if (!file_exists($this->path . $mailboxName)) {
            $this->logger->notice('Adding cache path for mailbox {mailbox}', ['mailbox' => $mailboxName]);
            if (!mkdir($this->path . $mailboxName, 0777, true)) {
                throw new \RuntimeException('Failed to create ' . $this->path . $mailboxName);
            }
        }
        $path = tempnam($this->path . $mailboxName, 'mail_');
        if ($path === false) {
            throw new \InvalidArgumentException('Failed to generate tmpname for mail ' . $message->getSubject());
        }
        if (file_put_contents($path, $message->toString()) === false) {
            throw new \InvalidArgumentException('Failed to store mail ' . $message->getSubject());
        }
        $messageTime = (int)$message->getDate()->format('U');
        touch($path, $messageTime, $messageTime);
    }

    /**
     * Flush mailbox placement queue
     *
     * @param callable $handleMail Handler, gets mail content passed as argument; Must return true in case of success
     * @return void
     */
    public function flush(callable $handleMail): void
    {
        $begin     = microtime(true);
        $mailFiles = $this->listMailFiles();

        if (!count($mailFiles)) {
            $this->logger->info('Mailbox placement queue is empty');
            return;
        }

        if (!$this->lockImapService()) {
            $this->logger->info(
                'Unable to lock mailbox placement queue, trying to flush {mailfiles} mails',
                ['mailFiles' => count($mailFiles)]
            );
            return;
        }

        foreach ($mailFiles as $mailFilePath) {
            $mailFileStream = fopen($mailFilePath, 'r');
            if (!flock($mailFileStream, LOCK_EX)) {
                $this->logger->notice(
                    'Unable to lock mail file {path}, skipping',
                    ['path' => $mailFilePath]
                );
                continue;
            }
            if ($handleMail($mailFilePath)) {
                $this->logger->info(
                    'Stored {path} in mailbox, going to remove file',
                    ['path' => $mailFilePath]
                );
                if (!unlink($mailFilePath)) {
                    $this->logger->error(
                        'Failed to remove sent mail {path}',
                        ['path' => $mailFilePath]
                    );
                }
                fclose($mailFileStream);
            } else {
                flock($mailFileStream, LOCK_UN);
                $this->logger->warning(
                    'Failed to store mail {path} in mailbox, unlocking',
                    ['path' => $mailFilePath]
                );
            }
        } //foreach

        $this->unlockImapService();
        $this->logger->notice(
            'Flushed mail queue in {duration} seconds with {count} items',
            ['duration' => round(microtime(true) - $begin), 'count' => count($mailFiles)]
        );
    }

    /**
     * Release lock on imap service
     *
     * @return void
     */
    private function unlockImapService(): void
    {
        if ($this->imapServiceLockHandle) {
            flock($this->imapServiceLockHandle, LOCK_UN);
            fclose($this->imapServiceLockHandle);
            $this->imapServiceLockHandle = null;
        }
    }

    /**
     * Provides path to imap service lock file
     *
     * @return string
     */
    private function imapServiceLockFilePath(): string
    {
        return $this->path . self::SERVICE_LOCK_FILE;
    }

    /**
     * Try to lock imap service
     *
     * @param int $waitSeconds Time to try lock until giving up
     * @return bool
     */
    private function lockImapService(int $waitSeconds = 20): bool
    {
        if ($waitSeconds < 0 || $waitSeconds > 120) {
            throw new \InvalidArgumentException('Wait time ' . $waitSeconds . ' invalid');
        }
        $begin = microtime(true);

        while ((microtime(true) < $begin + $waitSeconds)) {
            if ($this->imapServiceLockHandle) {
                return true;
            }
            $lockHandle = fopen($this->imapServiceLockFilePath(), 'c');
            if (flock($lockHandle, LOCK_EX)) {
                $this->imapServiceLockHandle = $lockHandle;
                return true;
            }
            usleep(250);
        }

        return false;
    }

    /**
     * Provide listing of full paths to queued mail files
     *
     * @return array
     */
    private function listMailFiles(): array
    {
        $mailFiles = [];
        
        if (!is_readable($this->path)) {
            return [];
        }

        /** @var \SplFileInfo $item */
        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            $path     = $this->path . $iterator->getSubPathName();
            $filename = $item->getFilename();
            if (!$item->isDir() && preg_match('/^mail_/i', $filename)) {
                $mailFiles[] = $path;
            }
        }

        return $mailFiles;
    }
}
