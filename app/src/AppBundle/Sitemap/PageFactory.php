<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Sitemap;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class PageFactory
 *
 * @package AppBundle\Sitemap
 */
class PageFactory
{

    /**
     * Router used to create the routes for the transmitted pages
     *
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * PageFactory constructor.
     *
     * @param UrlGeneratorInterface $router
     */
    public function __construct(UrlGeneratorInterface $router) {
        $this->router = $router;
    }

    /**
     * Create @see Page related to transmitted $name whose last modification depends on transmitted $path modification
     *
     * @param string $path
     * @param string $name
     * @param float  $priority
     * @param string $changefreq
     * @return Page
     */
    public function createForPath($path, $name, $priority = 0.5, $changefreq = Page::CHANGEFREQ_WEEKLY) {
        $loc = $this->generateRoute($name);
        return Page::createForFile($path, $loc, $priority, $changefreq);
    }

    /**
     * Create @param string $name
     *
     * @param array $parameters
     * @param float $priority
     * @param \DateTimeInterface $lastMod
     * @param string $changefreq
     * @return Page
     * @see Page related to transmitted $name whose last modification depends on transmitted $path modification
     *
     */
    public function create(
        $name, $parameters = [],
        $priority = 0.5,
        \DateTimeInterface $lastMod = null,
        $changefreq = Page::CHANGEFREQ_WEEKLY
    )
    {
        $loc = $this->generateRoute($name, $parameters);
        return new Page($loc, $priority, $lastMod, $changefreq);
    }

    /**
     * Generate absolute path for transmitted route name, possibly with parameters
     *
     * @param string $name       The name of the route
     * @param mixed  $parameters An array of parameters
     * @return string The generated URL
     */
    public function generateRoute($name, $parameters = [])
    {
        return $this->router->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }


}