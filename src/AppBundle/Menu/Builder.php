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
     * Caches the user
     *
     * @var null|User
     */
    protected $user = null;

    /**
     * Defines whether the user was already fetched or not
     *
     * @var bool
     */
    protected $isUserFetched = false;

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
            if ($this->isUserAdmin()) {
                if ($this->userHasRole(User::ROLE_ADMIN_NEWSLETTER)) {
                    $menu->addChild('Newsletter', array('route' => 'newsletter_admin_overview'))
                         ->setAttribute('dropdown', true);
                    $menu['Newsletter']->addChild('Eigenes Abonnement', array('route' => 'newsletter_subscription'));
                    $menu['Newsletter']->addChild('Abonnements verwalten', array('route' => 'newsletter_admin_list'));
                    $menu['Newsletter']->addChild('Newsletter versenden', array('route' => 'newsletter_admin_send'));
                }

                if ($this->userHasRole(User::ROLE_ADMIN_EVENT)) {
                    $menu->addChild('Veranstaltungen', array('route' => 'event_list'))
                         ->setAttribute('dropdown', true);
                    $menu['Veranstaltungen']->addChild('Veranstaltungen verwalten', array('route' => 'event_list'));
                    $menu['Veranstaltungen']->addChild('Veranstaltung erstellen', array('route' => 'event_new'));

                    $menu->addChild('Felder', array('route' => 'acquisition_list'))
                         ->setAttribute('dropdown', true);
                    $menu['Felder']->addChild('Felder verwalten', array('route' => 'acquisition_list'));
                    $menu['Felder']->addChild('Feld erstellen', array('route' => 'acquisition_new'));
                }

                if ($this->userHasRole(User::ROLE_ADMIN_USER)) {
                    $menu->addChild('Benutzer', array('route' => 'user_list'));
                }
            } else {
                $menu->addChild('Newsletter', array('route' => 'newsletter_subscription'));
            }
        } else {
            $menu->addChild('Veranstaltungen', array('route' => 'homepage'));
            $menu->addChild('Newsletter', array('route' => 'newsletter_subscription'));
        }

        /*
        // access services from the container!
        $em = $this->container->get('doctrine')->getManager();
        // findMostRecent and Blog are just imaginary examples
        $blog = $em->getRepository('AppBundle:Blog')->findMostRecent();

                //->setAttribute('divider_append', true);

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
     * Fetch the user, using object cache
     *
     * @return User|null
     */
    protected function getUser()
    {
        if (!$this->isUserFetched) {
            $token = $this->container->get('security.token_storage')
                                     ->getToken();
            if ($token) {
                $this->user = $token->getUser();
            }
            $this->isUserFetched = true;
        }
        return $this->user;
    }

    /**
     * Check if a user is logged in
     *
     * @return bool
     */
    protected function userHasRole($role)
    {
        $user = $this->getUser();
        return ($user instanceof User && $user->hasRole($role));
    }

    /**
     * Check if a user is logged in
     *
     * @return bool
     */
    protected function isUserLoggedIn()
    {
        return ($this->getUser() instanceof User);
    }

    /**
     * Check if a user is admin
     *
     * @return bool
     */
    protected function isUserAdmin()
    {
        return $this->userHasRole('ROLE_ADMIN');
    }
}