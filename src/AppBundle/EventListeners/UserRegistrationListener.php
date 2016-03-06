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
            FOSUserEvents::REGISTRATION_COMPLETED => 'onUserRegisterComplete',
            FOSUserEvents::REGISTRATION_CONFIRMED => 'onUserRegisterConfirmed'
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
            $participationRepository = $this->doctrine->getRepository('AppBundle:Participation');

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

    /**
     * When user registration is confirmed
     *
     * @param FilterUserResponseEvent $event
     */
    public function onUserRegisterConfirmed(FilterUserResponseEvent $event)
    {
        $em = $this->doctrine->getManager();

        /** @var User $user */
        $user = $event->getUser();
        $user->addRole('ROLE_USER');
        $em->persist($user);
        $em->flush();
    }
}