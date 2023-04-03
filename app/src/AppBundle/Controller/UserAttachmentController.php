<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\User;
use AppBundle\Entity\UserAttachment;
use AppBundle\Entity\UserAttachmentRepository;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\InvalidTokenHttpException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;


class UserAttachmentController
{
    use DoctrineAwareControllerTrait, UploadedFileErrorHandlingTrait;

    /**
     * @var UserAttachmentRepository
     */
    private UserAttachmentRepository $repository;

    /**
     * security.csrf.token_manager
     *
     * @var CsrfTokenManagerInterface
     */
    private CsrfTokenManagerInterface $csrfTokenManager;

    /**
     * security.token_storage
     *
     * @var TokenStorageInterface|null
     */
    private ?TokenStorageInterface $tokenStorage;

    /**
     * router
     *
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ManagerRegistry            $doctrine
     * @param RouterInterface            $router
     * @param CsrfTokenManagerInterface  $csrfTokenManager
     * @param LoggerInterface            $logger
     * @param TokenStorageInterface|null $tokenStorage
     */
    public function __construct(
        ManagerRegistry           $doctrine,
        RouterInterface           $router,
        CsrfTokenManagerInterface $csrfTokenManager,
        LoggerInterface           $logger,
        ?TokenStorageInterface    $tokenStorage = null
    ) {
        $this->doctrine         = $doctrine;
        $this->repository       = $doctrine->getRepository(UserAttachment::class);
        $this->router           = $router;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->logger           = $logger;
        $this->tokenStorage     = $tokenStorage;
    }

    /**
     * @return Response
     * @CloseSessionEarly
     * @Route("/user_attachment_list", name="user_attachment_list")
     */
    public function listAttachmentsAction(): Response
    {
        return new JsonResponse(['attachments' => $this->fetchAttachmentList()]);
    }

    /**
     * @param Request $request
     * @return Response
     * @CloseSessionEarly
     * @Route("/user_attachment_upload", name="user_attachment_upload")
     */
    public function uploadAttachmentAction(Request $request): Response
    {
        $token = $request->request->get('token');

        if ($token != $this->csrfTokenManager->getToken('user-attachment')) {
            throw new InvalidTokenHttpException();
        }
        $em = $this->getDoctrine()->getManager();

        if (!$request->files->count()) {
            return new JsonResponse([]);
        }

        $user = $this->getUser();

        if (!$user) {
            throw new BadRequestHttpException('User required for file upload');
        }

        $result = [];

        /** @var UploadedFile $file */
        foreach ($request->files as $file) {
            $errorMessage = $this->provideFileErrorMessage($file);
            if ($errorMessage) {
                $result['errors'][] = $errorMessage;
            } else {
                $attachment = new UserAttachment($user, $file);
                $attachment->setFilenameOriginal($file->getClientOriginalName());
                $em->persist($attachment);
            }
        }
        $em->flush();

        return new JsonResponse(array_merge($result, ['attachments' => $this->fetchAttachmentList()]));
    }

    /**
     * @param Request $request
     * @param int     $id
     * @return Response
     * @CloseSessionEarly
     * @Route("/user_attachment_delete/{id}", name="user_attachment_delete", requirements={"id": "\d+"},
     *                                        methods={"POST"})
     */
    public function deleteAttachmentAction(Request $request, int $id)
    {
        $user = $this->getUser();
        if (!$user) {
            throw new BadRequestHttpException('User required for file download');
        }
        $attachment = $this->repository->find($id);
        if (!$attachment) {
            throw new NotFoundHttpException('User attachment #' . $id . ' not found');
        }
        if ($attachment->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedException('User attachment #' . $id . ' is related to another user');
        }

        $token = $request->request->get('token');
        if ($token != $this->csrfTokenManager->getToken('user-attachment-' . $id)) {
            throw new InvalidTokenHttpException();
        }
        
        $em = $this->doctrine->getManager();
        $em->remove($attachment);
        $em->flush();
        
        return new JsonResponse(['attachments' => $this->fetchAttachmentList()]);
    }

    /**
     * @param int    $id
     * @param string $filename
     * @return Response
     * @CloseSessionEarly
     * @Route("/user_attachment/{id}/{filename}", name="user_attachment_download",
     *      requirements={"id": "\d+", "token": ".*"})
     */
    public function downloadAttachmentAction(int $id, string $filename): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw new BadRequestHttpException('User required for file download');
        }
        $attachment = $this->repository->find($id);
        if (!$attachment) {
            throw new NotFoundHttpException('User attachment #' . $id . ' not found');
        }
        if ($attachment->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedException('User attachment #' . $id . ' is related to another user');
        }
        if ($attachment->getFilenameOriginal() !== $filename) {
            throw new BadRequestHttpException('User attachment #' . $id . ' has unexpected filename');
        }

        $file     = $attachment->getFile();
        $response = new BinaryFileResponse(
            $file->getPathname(),
            Response::HTTP_OK,
            [
                'Content-Length'      => $file->getSize(),
                'Last-Modified'       => $attachment->getCreatedAt()->format('r'),
                'Content-Disposition' => HeaderUtils::makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename, $filename
                ),
            ]
        );

        return $response;
    }

    /**
     * Fetch attachment list of user
     *
     * @return array
     */
    private function fetchAttachmentList(): array
    {
        $user = $this->getUser();
        if (!$user) {
            throw new BadRequestHttpException('User required for file upload');
        }

        $attachmentsData = [];
        $attachments     = $this->repository->findByUser($user);
        foreach ($attachments as $attachment) {
            $attachmentDeleteToken = $this->csrfTokenManager->getToken('user-attachment-' . $attachment->getId());
            $attachmentsData[]     = [
                'id'            => $attachment->getId(),
                'filesize'      => $attachment->getFile()->getSize(),
                'filename'      => $attachment->getFilenameOriginal(),
                'last_modified' => $attachment->getCreatedAt()->format(Event::DATE_FORMAT_DATE_TIME),
                'download'      => $this->router->generate(
                    'user_attachment_download',
                    [
                        'id'       => $attachment->getId(),
                        'filename' => $attachment->getFilenameOriginal(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'delete_token'  => $attachmentDeleteToken->getValue(),
                'delete'        => $this->router->generate(
                    'user_attachment_delete',
                    [
                        'id' => $attachment->getId(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ];
        }
        return $attachmentsData;
    }

    /**
     * Get a user from the Security Token Storage.
     *
     * @return User|null
     */
    protected function getUser(): ?User
    {
        if (!$this->tokenStorage || null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!\is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        if (!$user instanceof User) {
            throw new \InvalidArgumentException('Unknown user class: ' . get_class($user));
        }

        return $user;
    }
}
