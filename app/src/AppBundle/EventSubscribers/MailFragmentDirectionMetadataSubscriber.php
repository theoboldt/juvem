<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\EventSubscribers;

use AppBundle\Mail\MailConfigurationProvider;
use AppBundle\Mail\MailFragment;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\StaticPropertyMetadata;

class MailFragmentDirectionMetadataSubscriber implements EventSubscriberInterface
{
    
    /**
     * @var MailConfigurationProvider
     */
    private MailConfigurationProvider $mailConfigurationProvider;
    
    /**
     * @param MailConfigurationProvider $mailConfigurationProvider
     */
    public function __construct(MailConfigurationProvider $mailConfigurationProvider)
    {
        $this->mailConfigurationProvider = $mailConfigurationProvider;
    }
    
    /**
     * @inheritdoc
     */
    static public function getSubscribedEvents()
    {
        return [
            [
                'event'  => 'serializer.post_serialize',
                'class'  => MailFragment::class,
                'method' => 'onPostSerialize'
            ],
        ];
    }
    
    public function onPostSerialize(ObjectEvent $event)
    {
        $mail    = $event->getObject();
        $visitor = $event->getVisitor();
        
        if (!$mail instanceof MailFragment || !$visitor instanceof JsonSerializationVisitor) {
            return;
        }
        $organizationAddresses = [
            $this->mailConfigurationProvider->organizationEmail(),
            $this->mailConfigurationProvider->getMailerAddress(),
        ];
        $fromOrganization      = false;
        $toOrganization        = false;
    
        foreach ($mail->getFrom() as $mailFrom) {
            if (in_array($mailFrom, $organizationAddresses)) {
                $fromOrganization = true;
            }
        }
        foreach ($mail->getTo() as $mailTo) {
            if (in_array($mailTo, $organizationAddresses)) {
                $toOrganization = true;
            }
        }
        
        $visitor->visitProperty(new StaticPropertyMetadata('', 'organization_receiver', null), $toOrganization);
        $visitor->visitProperty(new StaticPropertyMetadata('', 'organization_sender', null), $fromOrganization);
    }
}