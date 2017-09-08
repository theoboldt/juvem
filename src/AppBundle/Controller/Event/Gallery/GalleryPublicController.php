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


use AppBundle\Entity\Event;
use AppBundle\Entity\GalleryImage;
use AppBundle\Entity\User;
use AppBundle\ImageResponse;
use Imagine\Image\ImageInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class GalleryPublicController extends BaseGalleryController
{

    /**
     * Page for list of events
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/gallery/{hash}", requirements={"eid": "\d+"}, name="event_gallery")
     */
    public function showAction(Event $event, $hash)
    {
        $repository  = $this->getDoctrine()->getRepository(GalleryImage::class);
        $images      = $repository->findByEvent($event);
        $galleryHash = $this->galleryHash($event);

        if (!hash_equals($galleryHash, $hash) || !$event->getIsGalleryLinkSharing()) {
            throw new NotFoundHttpException('Hash incorrect or link sharing disabled');
        }

        return $this->render(
            'event/public/gallery.html.twig',
            [
                'event'       => $event,
                'galleryHash' => $galleryHash,
                'images'      => $images
            ]
        );
    }

    /**
     * Detail page for one single event
     *
     * @ParamConverter("galleryImage", class="AppBundle:GalleryImage", options={"id" = "iid"})
     * @Route("/event/{eid}/gallery/{iid}/thumbnail/{hash}", requirements={"eid": "\d+", "iid": "\d+",},
     *                                               name="gallery_image_thumbnail")
     * @Route("/event/{eid}/gallery/{iid}/thumbnail", requirements={"eid": "\d+", "iid": "\d+",},
     *                                                name="gallery_image_thumbnail_without_hash")
     * @param  string       $hash
     * @param  GalleryImage $galleryImage
     * @return ImageResponse
	 * @throws NotFoundHttpException If no access granted
     */
    public function thumbnailImageAction(GalleryImage $galleryImage, $hash = null)
    {
        if (!$this->isAccessGranted($galleryImage, $hash)) {
            throw new NotFoundHttpException('Not allowed to access image');
        }

        $uploadManager = $this->get('app.gallery_image_manager');
        $image         = $uploadManager->fetchResized(
            $galleryImage->getFilename(), GalleryImage::THUMBNAIL_DIMENSION, GalleryImage::THUMBNAIL_DIMENSION,
            ImageInterface::THUMBNAIL_OUTBOUND, 30
        );

        return new ImageResponse($image);
    }

    /**
     * @ParamConverter("galleryImage", class="AppBundle:GalleryImage", options={"id" = "iid"})
     * @Route("/event/{eid}/gallery/{iid}/detail/{hash}", requirements={"eid": "\d+", "iid": "\d+",},
     *                                                    name="gallery_image_detail")
     * @Route("/event/{eid}/gallery/{iid}/detail", requirements={"eid": "\d+", "iid": "\d+",},
     *                                             name="gallery_image_detail_without_hash")
	 * defaults={"_format": "html"}
     * @param  string       $hash
     * @param  GalleryImage $galleryImage
     * @return ImageResponse
	 * @throws NotFoundHttpException If no access granted
     */
    public function detailImageAction(GalleryImage $galleryImage, $hash = null)
    {
        if (!$this->isAccessGranted($galleryImage, $hash)) {
            throw new NotFoundHttpException('Not allowed to access image');
        }

        $uploadManager = $this->get('app.gallery_image_manager');
        $image         = $uploadManager->fetchResized(
            $galleryImage->getFilename(), GalleryImage::THUMBNAIL_DETAIL, GalleryImage::THUMBNAIL_DETAIL,
            ImageInterface::THUMBNAIL_INSET, 80
        );

        return new ImageResponse($image);
    }

    /**
     * Detail page for one single event
     *
     * @ParamConverter("galleryImage", class="AppBundle:GalleryImage", options={"id" = "iid"})
     * @Route("/event/{eid}/gallery/{iid}/original/{hash}", requirements={"eid": "\d+", "iid": "\d+",},
     *                                                      name="gallery_image_original")
     * @param  string       $hash
     * @param  GalleryImage $galleryImage
     * @return ImageResponse
	 * @throws NotFoundHttpException If no access granted
     */
    public function originalImageAction($hash, GalleryImage $galleryImage)
    {
        if (!$this->isAccessGranted($galleryImage, $hash)) {
            throw new NotFoundHttpException('Not allowed to access image');
        }

        $uploadManager = $this->get('app.gallery_image_manager');
        $image         = $uploadManager->fetch($galleryImage->getFilename());


        return new ImageResponse($image);
    }

    /**
     * Check if access is granted for gallery
     *
     * @param GalleryImage $galleryImage
     * @param string       $hash
     * @return bool
     */
    private function isAccessGranted(GalleryImage $galleryImage, $hash = null)
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user && $user->hasRole('ROLE_ADMIN_EVENT')) {
            return true;
        }
        $event = $galleryImage->getEvent();
        if ($event->isGalleryLinkSharing() && hash_equals($this->galleryHash($event), $hash)) {
            return true;
        }

        return false;
    }
}