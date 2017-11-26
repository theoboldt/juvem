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

class NewsletterManager extends AbstractMailerAwareManager
{

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
    protected function mail(NewsletterSubscription $subscription, $template)
    {
        $message  = $this->mailGenerator->getMessage(
            $template,
            ['subscription' => $subscription]
        );
        $nameLast = $subscription->getNameLast();
        $message->setTo($subscription->getEmail(), $nameLast ? $nameLast : null);

        return $this->mailer->send($message);
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

        $sentCount  = 0;
        $exceptions = [];

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

                $resultCount = 0;
                try {
                    $resultCount = $this->mailer->send($message);
                    $sentCount   += $resultCount;
                    $newsletter->addRecipient($subscription);
                } catch (\Exception $e) {
                    $exceptions[] = $e;
                }

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

        if (count($exceptions)) {
            /** @var \Exception $exception */
            foreach ($exceptions as $exception) {
                $this->logger->error(
                    'Exception occured while sending in file {file}:{line}: {message}',
                    [
                        'file'    => $exception->getFile(),
                        'line'    => $exception->getLine(),
                        'message' => $exception->getMessage()
                    ]
                );
            }
        }

        return $sentCount;
    }
}