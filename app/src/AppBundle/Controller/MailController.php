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
use AppBundle\JsonResponse;
use AppBundle\Mail\MailboxNotFoundException;
use AppBundle\Mail\MailListService;
use AppBundle\SerializeJsonResponse;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\ChangeTracking\EntityChange;
use AppBundle\Entity\ChangeTracking\EntityChangeRepository;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class MailController
{
    private ManagerRegistry $registry;
    
    private MailListService $mailListService;
    
    /**
     * MailController constructor.
     *
     * @param ManagerRegistry $registry
     * @param MailListService $mailListService
     */
    public function __construct(ManagerRegistry $registry, MailListService $mailListService)
    {
        $this->registry        = $registry;
        $this->mailListService = $mailListService;
    }
    
    
    /**
     * Get list of changes for transmitted entity
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
        /** @var EntityChangeRepository $repository */
        $repository = $this->registry->getRepository(EntityChange::class);
        $className  = EntityChangeRepository::convertRouteToClassName($classDescriptor);
        
        $relatedRepository = $this->registry->getRepository($className);
        $relatedEntity     = $relatedRepository->find($entityId);
        if (!$relatedEntity) {
            throw new NotFoundHttpException('Failed to find related entity');
        }
    
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
        // required to generate filename header
        $responseBinary = new BinaryFileResponse(__FILE__);
        $responseBinary->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT, $mail->getSubject() . '.eml'
        );
        $headerContentDisposition = $responseBinary->headers->get('Content-Disposition');
        
        $messageCallback = $this->mailListService->provideRawMessageCallback($mail);
        $response        = new StreamedResponse(
            $messageCallback, 200, ['Content-Disposition' => $headerContentDisposition]
        );
        return $response;
    }
}