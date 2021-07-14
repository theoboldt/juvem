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
use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use Ddeboer\Imap\Message\EmailAddress;
use Ddeboer\Imap\Search\Text\Text;
use Ddeboer\Imap\SearchExpression;
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
     * MailListService constructor.
     *
     * @param CacheInterface $cacheAppEmail
     * @param MailImapService $mailImapService
     */
    public function __construct(CacheInterface $cacheAppEmail, MailImapService $mailImapService)
    {
        $this->cache           = $cacheAppEmail;
        $this->mailImapService = $mailImapService;
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
            || $class === NewsletterSubscription::class
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
     * Find emails related to given entity
     *
     * @param string $class
     * @param int $id
     * @return mixed
     */
    public function findEmailsRelatedToEntity(string $class, int $id)
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
                        MailSendService::HEADER_RELATED_ENTITY_TYPE . ': ' . $class,
                    )
                );
                $search->addCondition(
                    new Text(
                        MailSendService::HEADER_RELATED_ENTITY_ID . ': ' . $id,
                    )
                );
                $mailboxes = $this->mailImapService->getMailboxes();
                $result    = [];
                foreach ($mailboxes as $mailbox) {
                    $messages = $mailbox->getMessages($search);
                    foreach ($messages as $message) {
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
                            /** @var EmailAddress $address */
                            foreach ($message->getTo() as $address) {
                                $toList[] = $address->getAddress();
                            }
                        }
                        $result[] = new MailFragment(
                            $fromList,
                            $toList,
                            $message->getSubject(),
                            $message->getDate(),
                            $mailbox->getName(),
                            count($message->getAttachments())
                        );
                    }
                }
    
                usort(
                    $result, function (MailFragment $a, MailFragment $b) {
                    return $b->getDate() <=> $a->getDate();
                }
                );
                
                
                return $result;
            }
        );
    }
    
    
}