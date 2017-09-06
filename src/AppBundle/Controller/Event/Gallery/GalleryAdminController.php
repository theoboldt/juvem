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
use AppBundle\ImageResponse;
use AppBundle\InvalidTokenHttpException;
use Imagine\Image\ImageInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class GalleryAdminController extends Controller
{

    /**
     * Page for list of events
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/gallery", requirements={"eid": "\d+"}, name="event_gallery_admin")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function detailsAction(Event $event)
    {
        $repository = $this->getDoctrine()->getRepository(GalleryImage::class);
        $images     = $repository->findBy(['event' => $event]);

        return $this->render(
            'event/admin/gallery-detail.html.twig',
            [
                'event'  => $event,
                'images' => $images
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
            $em->persist($galleryImage);
        }
        $em->flush();

        return new JsonResponse(['eid' => $event->getEid(), 'iid' => $galleryImage->getIid()]);
    }

    /**
     * Detail page for one single event
     *
     * @ParamConverter("galleryImage", class="AppBundle:GalleryImage", options={"id" = "iid"})
     * @Route("/event/{eid}/gallery/{iid}/thumbnail", requirements={"eid": "\d+", "iid": "\d+",},
     *                                               name="gallery_image_thumbnail")
     * @param GalleryImage $galleryImage
     * @return ImageResponse
     */
    public function thumbnailImageAction(GalleryImage $galleryImage)
    {
        $uploadManager = $this->get('app.gallery_image_manager');
        $image         = $uploadManager->fetchResized(
            $galleryImage->getFilename(), 150, 150, ImageInterface::THUMBNAIL_OUTBOUND, 30
        );

        return new ImageResponse($image);
    }

    /**
     * Detail page for one single event
     *
     * @ParamConverter("galleryImage", class="AppBundle:GalleryImage", options={"id" = "iid"})
     * @Route("/event/{eid}/gallery/{iid}/original", requirements={"eid": "\d+", "iid": "\d+",},
     *                                               name="gallery_image_original")
     * @param GalleryImage $galleryImage
     * @return ImageResponse
     */
    public function originalImageAction(GalleryImage $galleryImage)
    {
        $uploadManager = $this->get('app.gallery_image_manager');
        $image         = $uploadManager->fetch($galleryImage->getFilename());

        return new ImageResponse($image);
    }


    /**
     * Detail page for one single event
     *
     * @Route("/admin/mail/template", name="mail_template")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function emailTemplateAction()
    {
        return $this->render('mail/notify-participants.html.twig');
    }
}