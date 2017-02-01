<?php

namespace AppBundle\Manager;

use AppBundle\Entity\Newsletter;
use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Entity\User;

class NewsletterManager extends AbstractMailerAwareManager
{

    /**
     * Send a newsletter subscription request email
     *
     * @param NewsletterSubscription $subscription
     */
    public function mailNewsletterSubscriptionRequested(NewsletterSubscription $subscription)
    {
        $message  = $this->mailGenerator->getMessage(
            'newsletter-subscription-requested',
            array('subscription' => $subscription)
        );
        $nameLast = $subscription->getNameLast();
        $message->setTo($subscription->getEmail(), $nameLast ? $nameLast : null);

        $this->mailer->send($message);
    }

    /**
     * Send a newsletter subscription request email
     *
     * @param NewsletterSubscription $subscription
     */
    public function mailNewsletterSubscriptionImported(NewsletterSubscription $subscription)
    {
        $message  = $this->mailGenerator->getMessage(
            'newsletter-subscription-import',
            array('subscription' => $subscription)
        );
        $nameLast = $subscription->getNameLast();
        $message->setTo($subscription->getEmail(), $nameLast ? $nameLast : null);

        $this->mailer->send($message);
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

            $contentHtml = htmlentities($content);

            if ($area == 'content') {
                $contentHtml = str_replace(array("\n\n", "\r\r", "\r\n\r\n"), '</p><p>', $contentHtml);
            }

            $dataHtml[$area] = $contentHtml;
        }
        unset($content);

        $this->logger->info(
            'Going to send newsletter {lid} for {count} recipients',
            ['lid' => $newsletter->getLid(), 'count' => count($subscriptions)]
        );

        $sentCount = 0;

        /** @var NewsletterSubscription $subscription */
        foreach ($subscriptions as $subscription) {
            $startTime = microtime(true);
            if ($subscription->getIsEnabled() && $subscription->getIsConfirmed()) {
                $dataBoth     = array(
                    'text' => $dataText,
                    'html' => $dataHtml
                );
                $email        = $subscription->getEmail();
                $firstName    = '';
                $lastName     = $subscription->getNameLast();
                $assignedUser = $subscription->getAssignedUser();
                if ($assignedUser) {
                    $lastName  = $assignedUser->getNameLast();
                    $firstName = $assignedUser->getNameFirst();
                }

                $message = $this->mailGenerator->getMessage('general-raw', $dataBoth);

                if ($assignedUser) {
                    $message->setTo(
                        $email,
                        (User::fullname($lastName, $firstName))
                    );
                } elseif ($lastName) {
                    $message->setTo($email, $lastName);
                } else {
                    $message->setTo($email);
                }

                $resultCount = $this->mailer->send($message);
                $sentCount += $resultCount;
                $newsletter->addRecipient($subscription);

                $duration = round(microtime(true) - $startTime);
                if ($resultCount) {
                    $this->logger->info(
                        'Sent newsletter to {rid} in {duration} seconds',
                        ['rid' => $subscription->getRid(), 'duration' => $duration]
                    );
                } else {
                    $this->logger->error(
                        'Failed to send newsletter to {rid} in {duration} seconds',
                        ['rid' => $subscription->getRid(), 'duration' => $duration]
                    );
                }
            }
        }

        return $sentCount;
    }
}