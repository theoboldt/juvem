<?php

namespace AppBundle\Manager;

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
     * Send a newsletter email
     *
     * @param array                          $data          The custom text for email
     * @param array|NewsletterSubscription[] $subscriptions A list of newsletter subscriptions which should receive
     *                                                      this newsletter
     */
    public function mailNewsletter(array $data, array $subscriptions)
    {
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

        /** @var NewsletterSubscription $subscription */
        foreach ($subscriptions as $subscription) {
            if ($subscription->getIsEnabled()) {
                $dataBoth     = array('text' => $dataText,
                                      'html' => $dataHtml
                );
                $email        = $subscription->getEmail();
                $lastName     = $subscription->getNameLast();
                $assignedUser = $subscription->getAssignedUser();
                if ($assignedUser) {
                    $lastName  = $assignedUser->getNameLast();
                    $firstName = $assignedUser->getNameFirst();
                }


                $message = $this->mailGenerator->getMessage(
                    'general-raw', $dataBoth
                );

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

                $this->mailer->send($message);

            }
        }

    }
}