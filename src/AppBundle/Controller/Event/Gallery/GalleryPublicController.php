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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class GalleryPublicController extends BaseGalleryController
{

    /**
     * Page for list of events
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/gallery/{hash}", requirements={"eid": "\d+"}, name="event_gallery")
     * @Route("/event/{eid}/gallery/{hash}/", requirements={"eid": "\d+"})
     */
    public function showAction(Request $request, Event $event, $hash = null)
    {
        $repository  = $this->getDoctrine()->getRepository(GalleryImage::class);
        $images      = $repository->findByEvent($event);
        $galleryHash = $this->galleryHash($event);
    
        $lastModified = $event->getModifiedAt();
        $eTag         = $lastModified->format('U');
        $galleries    = [];
    
        foreach ($images as $image) {
            $imageModified = $image->getModifiedAt();
            $eTag          .= $image->getIid() . $imageModified->format('U');
            if ($imageModified > $lastModified) {
                $lastModified = $imageModified;
            }
            $recordedDate = $image->getRecordedAt();
            if ($recordedDate instanceof \DateTimeInterface && $recordedDate->format('Y') > 2000) {
                switch((int)$recordedDate->format('m')) {
                    case 1:
                        $month = 'Januar';
                        break;
                    case 2:
                        $month = 'Februar';
                        break;
                    case 3:
                        $month = 'März';
                        break;
                    case 4:
                        $month = 'April';
                        break;
                    case 5:
                        $month = 'Mai';
                        break;
                    case 6:
                        $month = 'Juni';
                        break;
                    case 7:
                        $month = 'Juli';
                        break;
                    case 8:
                        $month = 'August';
                        break;
                    case 9:
                        $month = 'September';
                        break;
                    case 10:
                        $month = 'Oktober';
                        break;
                    case 11:
                        $month = 'November';
                        break;
                    case 12:
                        $month = 'Dezember';
                        break;
                    default:
                        throw new \InvalidArgumentException();
                }
                $key = $recordedDate->format('d').'. '.$month.' '.$recordedDate->format('Y');
            } else {
                $key = '';
            }
            if (!isset($galleries[$key])) {
                $galleries[$key] = [];
            }
            $galleries[$key][] = $image;
        }
        $eTag = sha1($eTag);

        if (!$this->isAccessOnEventGranted($event, $hash)) {
            throw new NotFoundHttpException('Hash incorrect or link sharing disabled');
        }

        $grantedGalleries = $request->getSession()->get('grantedGalleries', []);
        $request->getSession()->set(
            'grantedGalleries', array_unique(array_merge($grantedGalleries, [$event->getEid()]))
        );

        $response = $this->render(
            'event/public/gallery.html.twig',
            [
                'event'       => $event,
                'galleryHash' => $galleryHash,
                'galleries'   => $galleries,
            ]
        );
        $response->setLastModified($lastModified)
                 ->setMaxAge(14 * 24 * 60 * 60)
                 ->setETag($eTag)
                 ->setPublic()
                 ->isNotModified($request);

        return $response;
    }

    /**
     * Preview for image
     *
     * @ParamConverter("galleryImage", class="AppBundle:GalleryImage", options={"id" = "iid"})
     * @Route("/event/{eid}/gallery/{iid}/preview/{hash}/{filename}", requirements={"eid": "\d+", "iid": "\d+"},
     *                                               name="gallery_image_preview")
     * @Route("/event/{eid}/gallery/{iid}/preview/{filename}", requirements={"eid": "\d+", "iid": "\d+"},
     *                                                name="gallery_image_preview_without_hash")
     * @param Request       $request      Request used to ensure that user has visited overview page before
     * @param  GalleryImage $galleryImage Desired image
     * @param  string       $hash         Gallery hash to ensure that user is allowed to access image
     * @return ImageResponse|RedirectResponse
     */
    public function previewImageAction(Request $request, GalleryImage $galleryImage, $hash = null)
    {
        if (!$this->isAccessOnImageGranted($galleryImage, $hash)) {
            throw new NotFoundHttpException('Not allowed to access image');
        }
        $event = $galleryImage->getEvent();
        if (!in_array($event->getEid(), $request->getSession()->get('grantedGalleries', []))) {
            $route      = 'event_gallery';
            $parameters = ['eid' => $event->getEid()];
            if (!$hash) {
                $route              = 'event_gallery_admin';
                $parameters['hash'] = $hash;
            }
            return new RedirectResponse($this->generateUrl($route, $parameters));
        }

        $uploadManager = $this->get('app.gallery_image_manager');
        $image         = $uploadManager->fetchResized(
            $galleryImage->getFilename(), GalleryImage::PREVIEW_DIMENSION, GalleryImage::PREVIEW_DIMENSION,
            ImageInterface::THUMBNAIL_INSET, 50
        );

        return ImageResponse::createFromRequest($image, $request);
    }

    /**
     * Thumbnail for image
     *
     * @ParamConverter("galleryImage", class="AppBundle:GalleryImage", options={"id" = "iid"})
     * @Route("/event/{eid}/gallery/{iid}/thumbnail/{hash}/{filename}", requirements={"eid": "\d+", "iid": "\d+"},
     *                                               name="gallery_image_thumbnail")
     * @Route("/event/{eid}/gallery/{iid}/thumbnail/{filename}", requirements={"eid": "\d+", "iid": "\d+"},
     *                                                name="gallery_image_thumbnail_without_hash")
     * @param Request       $request      Request used to ensure that user has visited overview page before
     * @param  GalleryImage $galleryImage Desired image
     * @param  string       $hash         Gallery hash to ensure that user is allowed to access image
     * @return ImageResponse|RedirectResponse
     */
    public function thumbnailImageAction(Request $request, GalleryImage $galleryImage, $hash = null)
    {
        if (!$this->isAccessOnImageGranted($galleryImage, $hash)) {
            throw new NotFoundHttpException('Not allowed to access image');
        }
        $event = $galleryImage->getEvent();
        if (!in_array($event->getEid(), $request->getSession()->get('grantedGalleries', []))) {
            $route      = 'event_gallery';
            $parameters = ['eid' => $event->getEid()];
            if (!$hash) {
                $route              = 'event_gallery_admin';
                $parameters['hash'] = $hash;
            }
            return new RedirectResponse($this->generateUrl($route, $parameters));
        }

        $uploadManager = $this->get('app.gallery_image_manager');
        $image         = $uploadManager->fetchResized(
            $galleryImage->getFilename(), GalleryImage::THUMBNAIL_DIMENSION, GalleryImage::THUMBNAIL_DIMENSION,
            ImageInterface::THUMBNAIL_INSET, 15
        );

        return ImageResponse::createFromRequest($image, $request);
    }

    /**
     * @ParamConverter("galleryImage", class="AppBundle:GalleryImage", options={"id" = "iid"})
     * @Route("/event/{eid}/gallery/{iid}/detail/{hash}/{filename}", requirements={"eid": "\d+", "iid": "\d+"},
     *     defaults={"hash": "0"}, name="gallery_image_detail")
     *
     * @param Request       $request      Request used to ensure that user has visited overview page before
     * @param  GalleryImage $galleryImage Desired image
     * @param  string       $hash         Gallery hash to ensure that user is allowed to access image
     * @return ImageResponse|RedirectResponse
     * @throws NotFoundHttpException If no access granted
     */
    public function detailImageAction(Request $request, GalleryImage $galleryImage, $hash = null)
    {
        if (!$this->isAccessOnImageGranted($galleryImage, $hash)) {
            throw new NotFoundHttpException('Not allowed to access image');
        }
        $event = $galleryImage->getEvent();
        if (!in_array($event->getEid(), $request->getSession()->get('grantedGalleries', []))) {
            return new RedirectResponse($this->generateUrl('event_gallery', ['eid' => $event->getEid(), 'hash' => $hash]));
        }

        $uploadManager = $this->get('app.gallery_image_manager');
        $image         = $uploadManager->fetchResized(
            $galleryImage->getFilename(), GalleryImage::THUMBNAIL_DETAIL, GalleryImage::THUMBNAIL_DETAIL,
            ImageInterface::THUMBNAIL_INSET, 80
        );

        return ImageResponse::createFromRequest($image, $request);
    }

    /**
     * Detail page for one single event
     *
     * @ParamConverter("galleryImage", class="AppBundle:GalleryImage", options={"id" = "iid"})
     * @Route("/event/{eid}/gallery/{iid}/original/{hash}", requirements={"eid": "\d+", "iid": "\d+",},
     *                                                      name="gallery_image_original")
     * @param Request       $request      Request used to ensure that user has visited overview page before
     * @param  GalleryImage $galleryImage Desired image
     * @param  string       $hash         Gallery hash to ensure that user is allowed to access image
     * @return ImageResponse|RedirectResponse
     * @throws NotFoundHttpException If no access granted
     */
    public function originalImageAction(Request $request, $hash, GalleryImage $galleryImage)
    {
        if (!$this->isAccessOnImageGranted($galleryImage, $hash)) {
            throw new NotFoundHttpException('Not allowed to access image');
        }
        $event = $galleryImage->getEvent();
        if (!in_array($event->getEid(), $request->getSession()->get('grantedGalleries', []))) {
            return new RedirectResponse($this->generateUrl('event_gallery', ['eid' => $event->getEid(), 'hash' => $hash]));
        }

        $uploadManager = $this->get('app.gallery_image_manager');
        $image         = $uploadManager->fetch($galleryImage->getFilename());

        return ImageResponse::createFromRequest($image, $request);
    }

    /**
     * Check if access is granted for gallery image
     *
     * @param GalleryImage $galleryImage
     * @param string       $hash
     * @return bool
     */
    private function isAccessOnImageGranted(GalleryImage $galleryImage, $hash) {
        $event = $galleryImage->getEvent();
        return $this->isAccessOnEventGranted($event, $hash);
    }

    /**
     * Check if access is granted for event
     *
     * @param Event  $event
     * @param string $hash
     * @return bool
     */
    private function isAccessOnEventGranted(Event $event, $hash = '')
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user && $user->hasRole('ROLE_ADMIN_EVENT')) {
            return true;
        }
        if ($event->isGalleryLinkSharing() && hash_equals($this->galleryHash($event), (string)$hash)) {
            return true;
        }


        return false;
    }
}