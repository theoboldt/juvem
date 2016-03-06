<?php
namespace AppBundle\EventListeners;

use AppBundle\Entity\User;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use \Doctrine\Bundle\DoctrineBundle\Registry;
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
            FOSUserEvents::REGISTRATION_COMPLETED => 'onUserRegisterConfirm',
        );
    }

    /**
     * When user registered
     *
     * @param FilterUserResponseEvent $event
     */
    public function onUserRegisterConfirm(FilterUserResponseEvent $event)
    {
        /** @var User $user */
        $user = $event->getUser();
        if ($event->getRequest()
                  ->getSession()
                  ->has('participationList')
        ) {
            $user->setRoles(array('ROLE_USER'));

            $participationList       = $event->getRequest()
                                             ->getSession()
                                             ->get('participationList');
            $participationRepository = $this->doctrine->getRepository('AppBundle:Participation');

            $em = $this->doctrine->getManager();
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