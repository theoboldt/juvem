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
use AppBundle\InvalidTokenHttpException;
use Imagine\Image\ImageInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;


class GalleryAdminController extends BaseGalleryController
{

    /**
     * Page for list of events
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/gallery", requirements={"eid": "\d+"}, name="event_gallery_admin")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @param Request $request
     * @param Event   $event
     * @return Response
     */
    public function detailsAction(Request $request, Event $event)
    {
        $repository  = $this->getDoctrine()->getRepository(GalleryImage::class);
        $images      = $repository->findByEvent($event);
        $galleryHash = $this->galleryHash($event);

        $grantedGalleries = $request->getSession()->get('grantedGalleries', []);
        $request->getSession()->set(
            'grantedGalleries', array_unique(array_merge($grantedGalleries, [$event->getEid()]))
        );

        return $this->render(
            'event/admin/gallery.html.twig',
            [
                'event'       => $event,
                'galleryHash' => $galleryHash,
                'images'      => $images
            ]
        );
    }

    /**
     * Page for list of events
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/gallery/upload", requirements={"eid": "\d+"}, name="event_gallery_admin_upload")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function uploadImageAction(Request $request, Event $event)
    {
        $token = $request->request->get('token');
        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('gallery-upload-' . $event->getEid())) {
            throw new InvalidTokenHttpException();
        }
        $em = $this->getDoctrine()->getManager();

        if (!$request->files->count()) {
            return new JsonResponse([]);
        }

        /** @var UploadedFile $file */
        foreach ($request->files as $file) {
            $galleryImage = new GalleryImage($event, $file);
            try {
                $exif = exif_read_data($file->getPathname());
            } catch (\Exception $e) {
                $exif = [];
            }
            try {
                if (isset($exif['DateTimeOriginal'])) {
                    $recorded = new \DateTime($exif['DateTimeOriginal']);
                } elseif (isset($exif['DateTimeDigitized'])) {
                    $recorded = new \DateTime($exif['DateTimeDigitized']);
                } elseif (isset($exif['DateTime'])) {
                    $recorded = new \DateTime($exif['DateTime']);
                } else {
                    $recorded = null;
                }
                $galleryImage->setRecordedAt($recorded);

                list($width, $height) = getimagesize($file->getPathname());
                $galleryImage->setWidth($width);
                $galleryImage->setHeight($height);
            } catch (\Exception $e) {
                $this->get('logger')->error($e->getMessage());
            }
            $em->persist($galleryImage);
        }
        $em->flush();
        $iid = $galleryImage->getIid();

        $uploadManager = $this->get('app.gallery_image_manager');
        $this->get('event_dispatcher')->addListener(
            KernelEvents::TERMINATE, function (PostResponseEvent $event) use ($galleryImage, $uploadManager) {
            ignore_user_abort(true);
            if (ini_get('max_execution_time') < 10 * 60) {
                ini_set('max_execution_time', 10 * 60);
            }
            $uploadManager->fetchResized(
                $galleryImage->getFilename(), GalleryImage::THUMBNAIL_DIMENSION, GalleryImage::THUMBNAIL_DIMENSION,
                ImageInterface::THUMBNAIL_OUTBOUND, 30
            );
            $uploadManager->fetchResized(
                $galleryImage->getFilename(), GalleryImage::THUMBNAIL_DETAIL, GalleryImage::THUMBNAIL_DETAIL,
                ImageInterface::THUMBNAIL_INSET, 70
            );

            unset($galleryImage);
        });



        return new JsonResponse(['eid' => $event->getEid(), 'iid' => $iid]);
    }

    /**
     * @Route("/admin/event/gallery/image/delete", name="gallery_image_delete")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @return Response
     */
    public function deleteImageAction(Request $request)
    {
        $token = $request->get('_token');
        $iid   = $request->get('iid');

        $repository = $this->getDoctrine()->getRepository(GalleryImage::class);
        /** @var GalleryImage $image */
        $image      = $repository->find($iid);

        if ($image) {
            /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
            $csrf = $this->get('security.csrf.token_manager');
            $event = $image->getEvent();
            if ($token != $csrf->getToken('gallery-image-delete-' .$event->getEid())) {
                throw new InvalidTokenHttpException();
            }
            $em = $this->getDoctrine()->getManager();
            $em->remove($image);
            $em->flush();

        } else {
            throw new NotFoundHttpException('Image with transmitted iid not found');
        }

        return new JsonResponse([]);
    }

    /**
     * @Route("/admin/event/gallery/image/save", name="gallery_image_save")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @return Response
     */
    public function saveImageAction(Request $request)
    {
        $token = $request->get('_token');
        $iid   = $request->get('iid');
        $title = $request->get('title');

        $repository = $this->getDoctrine()->getRepository(GalleryImage::class);
        /** @var GalleryImage $image */
        $image = $repository->find($iid);

        if ($image) {
            /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
            $csrf = $this->get('security.csrf.token_manager');
            $event = $image->getEvent();
            if ($token != $csrf->getToken('gallery-image-save-' .$event->getEid())) {
                throw new InvalidTokenHttpException();
            }
            $image->setTitle($title);
            $image->setModifiedAtNow();
            $em = $this->getDoctrine()->getManager();
            $em->persist($image);
            $em->flush();

        } else {
            throw new NotFoundHttpException('Image with transmitted iid not found');
        }


        return new JsonResponse([]);
    }
}