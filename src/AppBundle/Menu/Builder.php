<?php
namespace AppBundle\Menu;

use AppBundle\Entity\User;
use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Generator for the main menu
     *
     * @param FactoryInterface $factory A menu factory interface
     * @return \Knp\Menu\ItemInterface          The configured menu
     */
    public function mainMenu(FactoryInterface $factory)
    {
        $menu = $factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav navbar-nav');

        if ($this->isUserLoggedIn()) {
            $menu->addChild('Teilnahmen', array('route' => 'public_participations'));
            $menu->addChild('Rundbriefe', array('route' => 'public_newsletter'));
            if ($this->isUserAdmin()) {
                $menu->addChild('Veranstaltungen', array('route' => 'event_list'));
                $menu->addChild('Felder', array('route' => 'acquisition_list'));
                $menu->addChild('Benutzer', array('route' => 'user_list'));
            }
        } else {
            $menu->addChild('Veranstaltungen', array('route' => 'homepage'));
        }

        /*
        // access services from the container!
        $em = $this->container->get('doctrine')->getManager();
        // findMostRecent and Blog are just imaginary examples
        $blog = $em->getRepository('AppBundle:Blog')->findMostRecent();

        $menu->addChild('Latest Blog Post', array(
        'route' => 'blog_show',
        'routeParameters' => array('id' => $blog->getId())
        ));
        */

        return $menu;
    }

    /**
     * Generator for the right side menu containing user related content
     *
     * @param FactoryInterface $factory A menu factory interface
     * @return \Knp\Menu\ItemInterface          The configured menu
     */
    public function authenticationMenu(FactoryInterface $factory)
    {
        $menu = $factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav navbar-nav navbar-right');

        if ($this->isUserLoggedIn()) {
            $menu->addChild('Abmelden', array('route' => 'fos_user_security_logout'));
        } else {
            $menu->addChild('Anmelden', array('route' => 'fos_user_security_login'));
            $menu->addChild('Registrieren', array('route' => 'fos_user_registration_register'));
        }

        return $menu;
    }

    /**
     * Check if a user is logged in
     *
     * @return bool
     */
    protected function isUserLoggedIn()
    {
        $token = $this->container->get('security.token_storage')
                                 ->getToken();
        if (!$token) {
            return false;
        }

        $user  = $token->getUser();

        return ($user instanceof User);
    }

    /**
     * Check if a user is admin
     *
     * @return bool
     */
    protected function isUserAdmin()
    {
        $token = $this->container->get('security.token_storage')
                                 ->getToken();
        if (!$token) {
            return false;
        }

        /** @var User $user */
        $user  = $token->getUser();

        return ($user && $user->hasRole('ROLE_ADMIN'));
    }
}