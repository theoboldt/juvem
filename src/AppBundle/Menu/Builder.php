<?php
namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function mainMenu(FactoryInterface $factory, array $options = array())
    {
        $menu = $factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav navbar-nav');

        $menu->addChild('Veranstaltungen', array('route' => 'event_list'));
        $menu->addChild('Benutzer', array('route' => 'user_list'));

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

}