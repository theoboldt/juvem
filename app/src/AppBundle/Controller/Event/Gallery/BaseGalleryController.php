<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Controller\Event\Gallery;


use AppBundle\Controller\DoctrineAwareControllerTrait;
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Controller\RoutingControllerTrait;
use AppBundle\Entity\Event;
use AppBundle\Manager\UploadImageManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

abstract class BaseGalleryController
{
    use DoctrineAwareControllerTrait, RoutingControllerTrait, RenderingControllerTrait;
    
    /**
     * kernel.secret
     *
     * @var string
     */
    private string $kernelSecret;
    
    /**
     * app.gallery_image_manager
     *
     * @var UploadImageManager
     */
    protected UploadImageManager $galleryImageManager;
    
    /**
     * BaseGalleryController constructor.
     *
     * @param string $kernelSecret
     * @param UploadImageManager $galleryImageManager
     * @param ManagerRegistry $doctrine
     * @param RouterInterface $router
     * @param Environment $twig
     */
    public function __construct(
        string $kernelSecret,
        UploadImageManager $galleryImageManager,
        ManagerRegistry $doctrine,
        RouterInterface $router,
        Environment $twig
    )
    {
        $this->kernelSecret        = $kernelSecret;
        $this->galleryImageManager = $galleryImageManager;
        $this->doctrine            = $doctrine;
        $this->router              = $router;
        $this->twig                = $twig;
    }
    
    
    /**
     * Generate gallery hash for transmitted event
     *
     * @param Event $event Event
     * @return string      SHA1 hash
     */
    protected function galleryHash(Event $event)
    {
        return sha1('gallery-hash-' . $this->kernelSecret . $event->getEid());
    }
    
}