<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\EventListeners;

use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener responsible to add participations to the user
 */
class UserRegistrationListener implements EventSubscriberInterface
{
    /**
     * Database abstraction
     *
     * @var Registry
     */
    protected $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::REGISTRATION_COMPLETED => 'onUserRegisterComplete'
        );
    }

    /**
     * When user registered
     *
     * @param FilterUserResponseEvent $event
     */
    public function onUserRegisterComplete(FilterUserResponseEvent $event)
    {
        /** @var User $user */
        $user = $event->getUser();

        if ($event->getRequest()
                  ->getSession()
                  ->has('participationList')
        ) {
            $em                      = $this->doctrine->getManager();
            $participationList       = $event->getRequest()
                                             ->getSession()
                                             ->get('participationList');
            $participationRepository = $this->doctrine->getRepository(Participation::class);

            foreach ($participationList as $pid) {
                $participation = $participationRepository->findOneBy(array('pid' => $pid));
                if ($participation) {
                    $participation->setAssignedUser($user);
                    $em->persist($participation);
                }
            }
            $em->flush();
        }
    }
}