<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager;

use AppBundle\Entity\Newsletter;
use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Entity\User;
use AppBundle\Mail\MailSendService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NewsletterManager
{
    
    /**
     * @var MailSendService
     */
    private MailSendService $mailService;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     * Initiate a participation manager service
     *
     * @param MailSendService $mailService
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        MailSendService $mailService,
        ?LoggerInterface $logger
    )
    {
        $this->mailService = $mailService;
        $this->logger      = $logger ?? new NullLogger();
    }
    
    /**
     * Send a newsletter subscription request email
     *
     * @param NewsletterSubscription $subscription
     * @return int  Number of successful recipients. Can be 0 which indicates failure
     */
    public function mailNewsletterSubscriptionRequested(NewsletterSubscription $subscription)
    {
        return $this->mail($subscription, 'newsletter-subscription-requested');
    }

    /**
     * Send a newsletter subscription import message
     *
     * @param NewsletterSubscription $subscription
     * @return int  Number of successful recipients. Can be 0 which indicates failure
     */
    public function mailNewsletterSubscriptionImported(NewsletterSubscription $subscription)
    {
        return $this->mail($subscription, 'newsletter-subscription-import');
    }

    /**
     * Send a newsletter confirmation reminder email
     *
     * @param NewsletterSubscription $subscription
     * @return int  Number of successful recipients. Can be 0 which indicates failure
     */
    public function mailNewsletterSubscriptionConfirmationReminder(NewsletterSubscription $subscription)
    {
        return $this->mail($subscription, 'newsletter-subscription-reminder');
    }

    /**
     * Send mail to transmitted subscription using transmitted template
     *
     * @param NewsletterSubscription $subscription Target subscription
     * @param string                 $template     Mail template to use
     * @return int                                 Number of successful recipients. Can be 0 which indicates failure
     */
    protected function mail(NewsletterSubscription $subscription, string $template)
    {
        $message = $this->mailService->getTemplatedMessage(
            $template, ['subscription' => $subscription]
        );
        $nameLast = $subscription->getNameLast();
        $message->setTo($subscription->getEmail(), $nameLast ?: null);
        MailSendService::addRelatedEntityMessageHeader(
            $message, NewsletterSubscription::class, $subscription->getRid()
        );
        
        return $this->mailService->send($message);
    }

    /**
     * Send a newsletter email
     *
     * @param Newsletter                     $newsletter    Newsletter to send
     * @param array|NewsletterSubscription[] $subscriptions A list of newsletter subscriptions which should receive
     *                                                      this newsletter
     * @return int                                          Amount of sent messages
     */
    public function mailNewsletter(Newsletter $newsletter, array $subscriptions)
    {
        $data = [
            'subject' => $newsletter->getSubject(), 'title' => $newsletter->getTitle(),
            'lead'    => $newsletter->getLead(), 'content' => $newsletter->getContent()
        ];

        $dataText = array();
        $dataHtml = array();

        $content = null;
        foreach ($data as $area => $content) {
            $dataText[$area] = strip_tags($content);
            $dataHtml[$area] = $content;
        }
        unset($content);

        $this->logger->info(
            'Going to send newsletter {lid} for {count} recipients',
            ['lid' => $newsletter->getLid(), 'count' => count($subscriptions)]
        );
    
        $totalDuration = 0;
        $sentCount     = 0;
        
        /** @var NewsletterSubscription $subscription */
        foreach ($subscriptions as $subscription) {
            $startTime = microtime(true);
            if ($subscription->getIsEnabled() && $subscription->getIsConfirmed()) {
                if ($newsletter->getAgeRangeEnd() === 18 && $subscription->getAgeRangeBegin(true) > 18) {
                    $dataHtml['calltoactioncontent'] = sprintf(
                        '<p>Dieser Newsletter wurde Ihnen zugestellt, obwohl in Ihrem Abonnement eigentlich eine Altersspanne <i>%1$d bis %2$d Jahre</i> konfiguriert ist. Um auch über Veranstaltungen für jüngere Zielgruppen auf dem Laufenden zu bleiben, sollten Sie ihre abonnierte <a href="%3$s">Altersspanne korrigieren</a> oder das <a href="%3$s">mitwachsen der Altersspanne deaktivieren</a>.</p>
                        <p><a href="%3$s">Abonnement verwalten &raquo;</a></p>',
                        $subscription->getAgeRangeBegin(true),
                        $subscription->getAgeRangeEnd(true),
                        $this->mailService->generate(
                            'newsletter_subscription_token', ['token' => $subscription->getDisableToken()],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        )
                    );
                } else {
                    $dataHtml['calltoactioncontent'] = null;
                }
    
                $dataBoth = [
                    'text'         => $dataText,
                    'html'         => $dataHtml,
                    'subscription' => $subscription
                ];
                $email        = $subscription->getEmail();
                $firstName    = '';
                $lastName     = $subscription->getNameLast();
                $assignedUser = $subscription->getAssignedUser();
                if ($assignedUser) {
                    $lastName  = $assignedUser->getNameLast();
                    $firstName = $assignedUser->getNameFirst();
                }

                $message = $this->mailService->getTemplatedMessage('general-markdown', $dataBoth);
                MailSendService::addRelatedEntityMessageHeader(
                    $message, NewsletterSubscription::class, $subscription->getRid()
                );
                MailSendService::addRelatedEntityMessageHeader(
                    $message, Newsletter::class, $newsletter->getId()
                );

                if ($assignedUser) {
                    $message->setTo(
                        $email,
                        (User::generateFullname($lastName, $firstName))
                    );
                } elseif ($lastName) {
                    $message->setTo($email, $lastName);
                } else {
                    $message->setTo($email);
                }

                $abort       = false;
                $resultCount = 0;
                try {
                    $resultCount = $this->mailService->send($message);
                    $sentCount   += $resultCount;
                    $newsletter->addRecipient($subscription);
                } catch (\Exception $e) {
                    $this->logger->error(
                        'Exception occurred while sending in file {file}:{line} with message "{message}", trace: {trace}',
                        [
                            'file'    => $e->getFile(),
                            'line'    => $e->getLine(),
                            'message' => $e->getMessage(),
                            'trace'   => $e->getTraceAsString()
                        ]
                    );
                    if (strpos($e->getMessage(), 'Connection could not be established with host') !== false
                        || strpos($e->getMessage(), 'Mails per session limit exceeded') !== false
                    ) {
                        $abort = true;
                    }
                }
                
                $duration = round((microtime(true) - $startTime)*1000);
                $totalDuration += $duration;
                
                if ($resultCount) {
                    $this->logger->info(
                        'Sent newsletter to {rid} in {duration} ms',
                        ['rid' => $subscription->getRid(), 'duration' => $duration]
                    );
                } else {
                    $this->logger->error(
                        'Failed to send newsletter to {rid} in {duration} ms',
                        ['rid' => $subscription->getRid(), 'duration' => $duration]
                    );
                }
                if ($abort) {
                    $this->logger->warning(
                        'Permanent error occurred while sending messages, aborting message delivery'
                    );
                    break;
                }
            }
        }
        
        $this->logger->info(
            'Finished newsletter distribution within {duration} ms, sent {count} messages',
            ['count' => $sentCount, 'duration' => $totalDuration]
        );

        return $sentCount;
    }
}
