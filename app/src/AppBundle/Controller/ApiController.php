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

use AppBundle\Entity\User;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\Manager\Filesharing\NextcloudManager;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController
{
    use DoctrineAwareControllerTrait;
    
    private \Symfony\Component\Security\Core\Security $security;
    
    private LoggerInterface $logger;
    
    private ?NextcloudManager $cloudManager;
    
    /**
     * ApiController constructor.
     *
     * @param ManagerRegistry $doctrine
     * @param \Symfony\Component\Security\Core\Security $security
     * @param NextcloudManager|null $nextcloudManager
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ManagerRegistry $doctrine,
        \Symfony\Component\Security\Core\Security $security,
        ?NextcloudManager $nextcloudManager = null,
        LoggerInterface $logger = null
    )
    {
        $this->doctrine     = $doctrine;
        $this->security     = $security;
        $this->cloudManager = $nextcloudManager;
        $this->logger       = $logger ?: new NullLogger();
    }
    
    /**
     * @CloseSessionEarly
     * @Route("/api/cloud", name="api_cloud")
     * @Security("is_granted('cloud_access')")
     */
    public function connectCloudAction()
    {
        $user = $this->security->getUser();
        
        if (!$this->cloudManager) {
            $this->logger->warning(
                'Requested cloud connection for user {username} but cloud is disabled',
                ['username' => $user->getUsername()]
            );
            return new Response('', Response::HTTP_FORBIDDEN);
        }
        
        if ($user instanceof User && $user->getCloudUsername() === null) {
            $user->setCloudUsername($user->getEmail());
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            $this->logger->notice(
                'Configured cloud username {username} for user {id}',
                ['username' => $user->getUsername(), 'id' => $user->getId()]
            );
        }
        
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
