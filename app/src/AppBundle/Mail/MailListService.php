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

use AppBundle\Entity\ChangeTracking\EntityChangeRepository;
use AppBundle\Entity\Event;
use AppBundle\Entity\Newsletter;
use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use Ddeboer\Imap\Message\Attachment;
use Ddeboer\Imap\Message\EmailAddress;
use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\Search\Email\From;
use Ddeboer\Imap\Search\Email\To;
use Ddeboer\Imap\Search\Text\Text;
use Ddeboer\Imap\SearchExpression;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MailListService
{
    /**
     * Mail Listing cache
     *
     * @var CacheInterface
     */
    private CacheInterface $cache;
    
    /**
     * @var MailImapService
     */
    private MailImapService $mailImapService;
    
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    
    /**
     * MailListService constructor.
     *
     * @param CacheInterface $cacheAppEmail
     * @param MailImapService $mailImapService
     */
    public function __construct(
        CacheInterface  $cacheAppEmail,
        MailImapService $mailImapService,
        LoggerInterface $logger = null
    )
    {
        $this->cache           = $cacheAppEmail;
        $this->mailImapService = $mailImapService;
        $this->logger          = $logger ?? new NullLogger();
    }
    
    /**
     * Sort mail fragments descending
     *
     * @param MailFragment[] $mails
     */
    private static function sortMailFragmentsDateDesc(array &$mails): void
    {
        usort(
            $mails, function (MailFragment $a, MailFragment $b) {
            return $b->getDate() <=> $a->getDate();
        }
        );
    }
    
    /**
     * Determine if class is supported
     *
     * @param string $class
     * @return bool
     */
    public static function isClassSupported(string $class)
    {
        return (
            $class === Participation::class
            || $class === User::class
            || $class === Event::class
            || $class === NewsletterSubscription::class
            || $class === Newsletter::class
        );
    }
    
    /**
     * Create cache key
     *
     * @param string $class
     * @param int $id
     * @return string
     */
    public static function getCacheKey(string $class, int $id): string
    {
        return EntityChangeRepository::convertClassNameForRoute($class) . '_' . $id;
    }
    
    /**
     * Cache key for email address
     *
     * @param string $emailAddress
     * @return string
     */
    public static function getAddressCacheKey(string $emailAddress): string {
        return 'address_' . hash('sha256', $emailAddress);
    }
    
    /**
     * Clear cache
     *
     * @param string $emailAddress
     * @return void
     */
    public function clearEmailAddressCache(string $emailAddress): void
    {
        $this->cache->delete(self::getAddressCacheKey($emailAddress));
        $this->logger->notice('Cleared mail cache for address {address}', ['address', $emailAddress]);
    }
    
    /**
     * @param string $emailAddress
     * @return MailFragment[]
     */
    public function findEmailsRelatedToAddress(string $emailAddress): array
    {
        return $this->cache->get(
            self::getAddressCacheKey($emailAddress),
            function (ItemInterface $item) use ($emailAddress) {
                $search = new SearchExpression();
                $search->addCondition(new To($emailAddress));
                $result = $this->fetchMessagesForSearch($search);
                
                $search = new SearchExpression();
                $search->addCondition(new From($emailAddress));
                $result = array_merge($result, $this->fetchMessagesForSearch($search));
                
                $search = new SearchExpression();
                $search->addCondition(new Text('Final-Recipient: rfc822; ' . $emailAddress));
                $result = array_merge($result, $this->fetchMessagesForSearch($search));
                
                self::sortMailFragmentsDateDesc($result);
                
                return $result;
            }
        );
    }
    
    /**
     * Provide mail fragment for transmitted message identifiers
     *
     * @param string $mailboxName Mailbox
     * @param int $messageNumber  Message number
     * @return MailFragment
     */
    public function provideMailFragment(string $mailboxName, int $messageNumber): MailFragment
    {
        $message = $this->mailImapService->getMessageFromMailboxByNumber($mailboxName, $messageNumber);
        return self::convertMailboxEmailToMailFragment($message, $mailboxName);
    }
    
    /**
     * Provide callback which echoes raw message
     *
     * @param MailFragment $mailFragment
     * @return \Closure|null
     */
    public function provideRawMessageCallback(MailFragment $mailFragment)
    {
        $message = $this->mailImapService->getMessageFromMailboxByNumber(
            $mailFragment->getMailbox(), $mailFragment->getNumber()
        );
        if ($message) {
            $callback = function () use ($message) {
                echo $message->getRawMessage();
            };
            return $callback;
        }
        return null;
    }
    
    /**
     * Clear cache
     *
     * @param string $class
     * @param int $id
     * @return void
     */
    public function clearEntityRelatedCache(string $class, int $id): void
    {
        $this->cache->delete($this->getCacheKey($class, $id));
        $this->logger->notice('Cleared related mail cache for entity {class}:{id}', ['class' => $class, 'id' => $id]);
    }
    
    /**
     * Find emails related to given entity
     *
     * @param string $class
     * @param int $id
     * @return MailFragment[]
     */
    public function findEmailsRelatedToEntity(string $class, int $id): array
    {
        if (!self::isClassSupported($class)) {
            throw new UnsupportedEmailRelationException('Class ' . $class . ' is not supported');
        }
        
        return $this->cache->get(
            $this->getCacheKey($class, $id),
            function (ItemInterface $item) use ($class, $id) {
                $search = new SearchExpression();
                $search->addCondition(
                    new Text(
                        MailSendService::HEADER_RELATED_ENTITY . ': ' . $class . ':' . $id,
                    )
                );
                $result = $this->fetchMessagesForSearch($search);
               
                //LEGACY BEGIN
                $search = new SearchExpression();
                $search->addCondition(
                    new Text(
                        MailSendService::HEADER_RELATED_ENTITY_TYPE . ': ' . $class,
                    )
                );
                $search->addCondition(
                    new Text(
                        MailSendService::HEADER_RELATED_ENTITY_ID . ': ' . $id,
                    )
                );
                $result = array_merge($result, $this->fetchMessagesForSearch($search));
                //LEGACY END
                
                self::sortMailFragmentsDateDesc($result);
                
                return $result;
            }
        );
    }
    
    /**
     * Run configured in all mailboxes and provide aggregated result
     *
     * @param SearchExpression $searchExpression
     * @return MailFragment[]
     */
    private function fetchMessagesForSearch(SearchExpression $searchExpression): array
    {
        $mailboxes = $this->mailImapService->getMailboxes();
        $this->mailImapService->flushMailboxPlacementQueue();
        
        $result    = [];
        $timeBegin = microtime(true);
        foreach ($mailboxes as $mailbox) {
            $messages = $mailbox->getMessages($searchExpression);
            foreach ($messages as $message) {
                $result[] = $this->convertMailboxEmailToMailFragment($message, $mailbox->getName());
            }
        }
        $this->logger->notice(
            'Fetched {messages} messages for {mailboxes} mailboxes in {duration} seconds',
            [
                'messages' => count($result),
                'mailboxes' => count($mailboxes),
                'duration' => round(microtime(true) - $timeBegin)
            ]
        );
        return $result;
    }
    
    /**
     * Convert an IMAP message into internal mail fragment representation
     *
     * @param MessageInterface $message Message
     * @param string $mailboxName       Mailbox to store
     * @return MailFragment             Result
     */
    private function convertMailboxEmailToMailFragment(MessageInterface $message, string $mailboxName): MailFragment
    {
        $fromList    = [];
        $messageFrom = $message->getFrom();
        if ($messageFrom instanceof EmailAddress) {
            $fromList[] = $messageFrom->getAddress();
        } elseif (is_array($messageFrom)) {
            /** @var EmailAddress $address */
            foreach ($messageFrom as $address) {
                $fromList[] = $address->getAddress();
            }
        }
        $toList    = [];
        $messageTo = $message->getTo();
        if (is_array($messageTo)) {
            foreach ($message->getTo() as $address) {
                $toList[] = $address->getAddress();
            }
        }
        return new MailFragment(
            $message->getNumber(),
            $fromList,
            $toList,
            $message->getSubject(),
            $message->getDate(),
            $mailboxName,
            $this->provideMailAttachmentFragments($message)
        );
    }
    
    /**
     * Convert list of attachments to list of attachment fragments
     *
     * @param MessageInterface $message
     * @return MailAttachmentFragment[]
     */
    private function provideMailAttachmentFragments(MessageInterface $message): array
    {
        $fragmentAttachments = [];
        
        /** @var Attachment $attachment */
        foreach ($message->getAttachments() as $attachment) {
            $fragmentAttachments[] = new MailAttachmentFragment(
                $attachment->getPartNumber(),
                $attachment->getFilename(),
                $attachment->getSize(),
                $attachment->getType(),
                $message->getSubtype()
            );
        }
        
        return $fragmentAttachments;
    }
}
