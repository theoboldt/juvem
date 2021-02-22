<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Filesharing;


use AppBundle\Entity\Event;
use AppBundle\Entity\EventFileShare;
use AppBundle\Entity\EventFileShareRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventFileSharingManager
{
    /**
     * @var NextcloudManager|null
     */
    private ?NextcloudManager $nextcloudManager;
    
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;
    
    /**
     * EventFileSharingManager constructor.
     *
     * @param NextcloudManager|null $nextcloudManager
     * @param ManagerRegistry $doctrine
     */
    public function __construct(?NextcloudManager $nextcloudManager, ManagerRegistry $doctrine)
    {
        $this->nextcloudManager = $nextcloudManager;
        $this->doctrine         = $doctrine;
    }
    
    /**
     * Get repository
     *
     * @return EventFileShareRepository
     */
    private function getRepository(): EventFileShareRepository
    {
        return $this->doctrine->getRepository(EventFileShare::class);
    }
    
    /**
     * Provide all shares for event
     *
     * @param Event $event
     * @return EventFileShare[]
     */
    private function findSharesForEvent(Event $event): array
    {
        $repository = $this->getRepository();
        return $repository->findForEvent($event);
    }
    
    /**
     * Ensure cloud directories for team and management are available
     *
     * @param Event $event
     */
    public function ensureEventCloudSharesAvailable(Event $event): void
    {
        if (!$this->nextcloudManager) {
            return;
        }
        $em = $this->doctrine->getManager();
        
        if ($event->getShareDirectoryRootHref()) {
            $eventRootShareDirectoryName = NextcloudDirectory::extractDirectoryNameFromDirectoryHref(
                $event->getShareDirectoryRootHref()
            );
            $directory                   = $this->nextcloudManager->fetchEventRootDirectory(
                $eventRootShareDirectoryName
            );
            if ($directory == null) {
                throw new NextcloudEventShareRootDirectoryNotFoundException(
                    'Expected directory for event ' . $event->getEid() . ' at "' . $event->getShareDirectoryRootHref() .
                    '", but not found'
                );
            }
        } else {
            $directory = $this->nextcloudManager->createUniqueEventRootDirectory(
                $event->getTitle(), $event->getStartDate()
            );
            $event->setShareDirectoryRootHref($directory->getHref());
            $em->persist($event);
            $em->flush();
        }
        $shares = $this->findSharesForEvent($event);
        
        if (!self::filterShareByPurpose($shares, EventFileShare::PURPOSE_TEAM)) {
            $shareDirectory = $this->nextcloudManager->createEventTeamShare($directory);
            $share          = new EventFileShare(
                $event,
                EventFileShare::PURPOSE_TEAM,
                $shareDirectory->getFileId(),
                $shareDirectory->getHref(false),
                $shareDirectory->getName(),
                $shareDirectory->getName()
            );
            $em->persist($share);
            $em->flush();
        }
        
        if (!self::filterShareByPurpose($shares, EventFileShare::PURPOSE_MANAGEMENT)) {
            $shareDirectory = $this->nextcloudManager->createEventManagementShare($directory);
            $share          = new EventFileShare(
                $event,
                EventFileShare::PURPOSE_MANAGEMENT,
                $shareDirectory->getFileId(),
                $shareDirectory->getHref(false),
                $shareDirectory->getName(),
                $shareDirectory->getName()
            );
            $em->persist($share);
            $em->flush();
        }
    }
    
    /**
     * List files recursively
     *
     * @param Event $event
     * @param string $purpose
     * @return array
     */
    public function listFiles(Event $event, string $purpose): array
    {
        if (!$this->nextcloudManager) {
            return [];
        }
        $share = $this->getRepository()->findSinglePurposeForEvent($event, $purpose);
        if (!$share) {
            return [];
        }

        $files = $this->nextcloudManager->fetchDirectory($share->getDirectoryHref(), true);
        
        return $files;
    }
    
    /**
     * Ensure all users expected to have access on file share do have it
     *
     * @param Event $event
     * @return bool Returns true if something was updated
     */
    public function updateCloudShareAssignments(Event $event): bool
    {
        if (!$this->nextcloudManager) {
            return false;
        }
        $shares = $this->findSharesForEvent($event);

        if (!count($shares)) {
            return false;
        }
        
        $usersTeam       = [];
        $usersManagement = [];
        foreach ($event->getUserAssignments() as $userAssignment) {
            $cloudUserName = $userAssignment->getUser()->getCloudUsername();
            if ($cloudUserName) {
                if ($userAssignment->isAllowedCloudAccessTeam()) {
                    $usersTeam[] = $cloudUserName;
                }
                if ($userAssignment->isAllowedCloudAccessManagement()) {
                    $usersManagement[] = $cloudUserName;
                }
            }
        }
        
        $updated = false;
        foreach ($shares as $share) {
            if ($share->getPurpose() === EventFileShare::PURPOSE_TEAM) {
                $updated = true;
                $this->nextcloudManager->updateEventShareAssignments(
                    $share->getGroupName(),
                    $usersTeam
                );
            }
            if ($share->getPurpose() === EventFileShare::PURPOSE_MANAGEMENT) {
                $updated = true;
                $this->nextcloudManager->updateEventShareAssignments(
                    $share->getGroupName(),
                    $usersManagement
                );
            }
        }
        return (count($usersTeam) || count($usersManagement)) && $updated;
    }
    
    /**
     * Get share for purpose
     *
     * @param EventFileShare[] $shares
     * @param string $purpose
     * @return EventFileShare|null
     */
    private static function filterShareByPurpose(array $shares, string $purpose): ?EventFileShare
    {
        foreach ($shares as $share) {
            if ($share->getPurpose() === $purpose) {
                return $share;
            }
        }
        return null;
    }
}