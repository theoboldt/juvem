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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
    
        $form = $this->createFormBuilder()
                     ->add('action', HiddenType::class)
                     ->getForm();
    
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($images as $image) {
                $em->remove($image);
            }
            $em->flush();
            $images = [];
        
            return $this->redirectToRoute('event_gallery_admin', ['eid' => $event->getEid()]);
        }
    
        $grantedGalleries = $request->getSession()->get('grantedGalleries', []);
        $request->getSession()->set(
            'grantedGalleries', array_unique(array_merge($grantedGalleries, [$event->getEid()]))
        );
    
        return $this->render(
            'event/admin/gallery.html.twig',
            [
                'form'        => $form->createView(),
                'event'       => $event,
                'galleryHash' => $galleryHash,
                'images'      => $images,
            ]
        );
    }
    
    /**
     * Get list of all urls for all image previews/thumbnails etc.
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/gallery/image_urls.json", requirements={"eid": "\d+"}, name="event_gallery_image_urls_admin", methods={"GET"})
     * @Security("is_granted('read', event)")
     * @param Event $event Related event
     * @return JsonResponse
     */
    public function listGalleryImageUrls(Event $event) {
        $repository  = $this->getDoctrine()->getRepository(GalleryImage::class);
        $images      = $repository->findByEvent($event);
        $galleryHash = $this->galleryHash($event);
        $result = [];
    
        /** @var GalleryImage $image */
        foreach ($images as $image) {
            $params = [
                'eid'      => $event->getEid(),
                'iid'      => $image->getIid(),
                'hash'     => $galleryHash,
                'filename' => $image->getIid() . '.jpg'
            ];
            $result[] = $this->generateUrl('gallery_image_preview', $params);
            $result[] = $this->generateUrl('gallery_image_thumbnail', $params);
            $result[] = $this->generateUrl('gallery_image_detail', $params);
        }
        
        return new JsonResponse(['success' => true, 'urls' => $result]);
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
            list($width, $height) = getimagesize($file->getPathname(), $info);
            try {
                $exif = exif_read_data($file->getPathname(), 'ANY_TAG', true, false);
            } catch (\Exception $e) {
                $exif = [];
                $info = [];
            }
    
            $iptcCaption = '';
            $iptcTitle   = '';
            //extract title from iptc data
            if (isset($info['APP13'])) {
                $iptc        = iptcparse($info['APP13']);
                if (isset($iptc["1#090"]) && $iptc["1#090"][0] == "\x1B%G") {
                    $iptcCaption = str_replace("\000", "", $iptc["2#120"][0]);
                    $iptcCaption = utf8_decode($iptcCaption);
                }
                if (isset($iptc["2#105"]) && $iptc["2#105"][0] == "\x1B%G") {
                    $iptcTitle = str_replace("\000", "", $iptc["2#105"][0]);
                    $iptcTitle = utf8_decode($iptcTitle);
                }
            }
            
            try {
                if (isset($exif['EXIF']['DateTimeOriginal'])) {
                    $recorded = new \DateTime($exif['EXIF']['DateTimeOriginal']);
                } elseif (isset($exif['EXIF']['DateTimeDigitized'])) {
                    $recorded = new \DateTime($exif['EXIF']['DateTimeDigitized']);
                } elseif (isset($exif['EXIF']['DateTime'])) {
                    $recorded = new \DateTime($exif['EXIF']['DateTime']);
                } else {
                    $recorded = null;
                }
                $galleryImage->setRecordedAt($recorded);
                $galleryImage->setTitle($iptcTitle);
                $galleryImage->setCaption($iptcCaption);
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
        }
        );

        $template = $this->container->get('twig')->render(
            'event/public/embed-gallery-image.html.twig',
            [
                'eid'       => $event->getEid(),
                'hash'      => $this->galleryHash($event),
                'galleryId' => 0,
                'image'     => $galleryImage,
                'lightbox'  => false,
            ]
        );

        return new JsonResponse(
            ['eid' => $event->getEid(), 'iid' => $iid, 'template' => $template]
        );
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