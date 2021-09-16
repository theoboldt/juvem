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


use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\InvalidTokenHttpException;
use AppBundle\Mail\MailboxNotFoundException;
use AppBundle\Mail\MailListService;
use AppBundle\SerializeJsonResponse;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\ChangeTracking\EntityChange;
use AppBundle\Entity\ChangeTracking\EntityChangeRepository;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class MailController
{
    private ManagerRegistry $registry;
    
    private CsrfTokenManagerInterface $csrfTokenManager;
    
    private MailListService $mailListService;
    
    /**
     * MailController constructor.
     *
     * @param ManagerRegistry $registry
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param MailListService $mailListService
     */
    public function __construct(
        ManagerRegistry $registry, CsrfTokenManagerInterface $csrfTokenManager, MailListService $mailListService
    )
    {
        $this->registry         = $registry;
        $this->mailListService  = $mailListService;
        $this->csrfTokenManager = $csrfTokenManager;
    }
    
    /**
     * Clear list of related emails
     *
     * @CloseSessionEarly
     * @Route("/admin/related-emails-clear-cache/{classDescriptor}/{entityId}.json",
     *     requirements={"classDescriptor": "([a-zA-Z0-9_\.]+)", "entityId": "(\d+)"},
     *     name="admin_email_related_list_clear_cache", methods={"POST"})
     * @Security("is_granted('read_email')")
     * @param string $classDescriptor
     * @param int $entityId
     * @return Response
     */
    public function clearRelatedEmailCache(Request $request, string $classDescriptor, int $entityId): Response
    {
        $className     = EntityChangeRepository::convertRouteToClassName($classDescriptor);
        $relatedEntity = $this->fetchRelatedEntity($className, $entityId);
        
        $token = $request->get('_token');
        if ($token != $this->csrfTokenManager->getToken('related-emails-clear-cache')) {
            throw new InvalidTokenHttpException();
        }
        
        if ($relatedEntity instanceof Participation) {
            $this->mailListService->clearEmailAddressCache($relatedEntity->getEmail());
        } elseif ($relatedEntity instanceof NewsletterSubscription) {
            $this->mailListService->clearEmailAddressCache($relatedEntity->getEmail());
        } elseif ($relatedEntity instanceof User) {
            $this->mailListService->clearEmailAddressCache($relatedEntity->getEmail());
        } else {
            $this->mailListService->clearEntityRelatedCache($className, $entityId);
        }
        
        return $this->listRelatedEmails($classDescriptor, $entityId);
    }
    
    /**
     * Get list of related emails
     *
     * @CloseSessionEarly
     * @Route("/admin/related-emails/{classDescriptor}/{entityId}.json",
     *     requirements={"classDescriptor": "([a-zA-Z0-9_\.]+)", "entityId": "(\d+)"},
     *     name="admin_email_related_list", methods={"GET"})
     * @Security("is_granted('read_email')")
     * @param string $classDescriptor
     * @param int $entityId
     * @return Response
     */
    public function listRelatedEmails(string $classDescriptor, int $entityId): Response
    {
        $className     = EntityChangeRepository::convertRouteToClassName($classDescriptor);
        $relatedEntity = $this->fetchRelatedEntity($className, $entityId);

        if ($relatedEntity instanceof Participation) {
            $mails = $this->mailListService->findEmailsRelatedToAddress($relatedEntity->getEmail());
        } elseif ($relatedEntity instanceof NewsletterSubscription) {
            $mails = $this->mailListService->findEmailsRelatedToAddress($relatedEntity->getEmail());
        } elseif ($relatedEntity instanceof User) {
            $mails = $this->mailListService->findEmailsRelatedToAddress($relatedEntity->getEmail());
        } else {
            $mails = $this->mailListService->findEmailsRelatedToEntity($className, $entityId);
        }
    
        return new SerializeJsonResponse(['items' => $mails]);
    }
    
    /**
     * Fetch related entity
     *
     * @param string $className
     * @param int $entityId
     * @return object
     */
    private function fetchRelatedEntity(string $className, int $entityId): object
    {
        $relatedRepository = $this->registry->getRepository($className);
        $relatedEntity     = $relatedRepository->find($entityId);
        if (!$relatedEntity) {
            throw new NotFoundHttpException('Failed to find related entity');
        }
        return $relatedEntity;
    }
    
    /**
     * Get list of changes for transmitted entity
     *
     * @CloseSessionEarly
     * @Route("/admin/email/{mailboxName}/{messageNumber}.eml",
     *     requirements={"mailboxName": "([^\/]+)", "messageNumber": "(\d+)"},
     *     name="admin_email_download", methods={"GET"})
     * @Security("is_granted('read_email')")
     * @param string $mailboxName
     * @param int $messageNumber
     * @return Response
     */
    public function downloadRawEmail(string $mailboxName, int $messageNumber): Response
    {
        try {
            $mail = $this->mailListService->provideMailFragment($mailboxName, $messageNumber);
        } catch (MailboxNotFoundException $e) {
            throw new NotFoundHttpException(
                $e->getMessage(),
                $e
            );
        }
        $filename = $messageNumber . '.eml';
    
        $messageCallback = $this->mailListService->provideRawMessageCallback($mail);
        $response        = new StreamedResponse(
            $messageCallback, 200,
            [
                'Content-Disposition' => HeaderUtils::makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename, $filename
                )
            ]
        );
        return $response;
    }
}