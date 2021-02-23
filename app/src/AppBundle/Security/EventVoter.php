<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Security;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventUserAssignment;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;


class EventVoter extends AbstractDecisionManagerAwareVoter
{
    const READ                    = 'read';
    const EDIT                    = 'edit';
    const PARTICIPANTS_READ       = 'participants_read';
    const PARTICIPANTS_EDIT       = 'participants_edit';
    const EMPLOYEES_READ          = 'employees_read';
    const EMPLOYEES_EDIT          = 'employees_edit';
    const COMMENT_READ            = 'comment_read';
    const COMMENT_ADD             = 'comment_add';
    const CLOUD_ACCESS_TEAM       = 'cloud_access_team';
    const CLOUD_ACCESS_MANAGEMENT = 'cloud_access_management';
    
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array(
            $attribute,
            [
                self::READ,
                self::EDIT,
                self::PARTICIPANTS_READ,
                self::PARTICIPANTS_EDIT,
                self::EMPLOYEES_READ,
                self::EMPLOYEES_EDIT,
                self::COMMENT_READ,
                self::COMMENT_ADD,
                self::CLOUD_ACCESS_TEAM,
                self::CLOUD_ACCESS_MANAGEMENT
            ]
        )) {
            return false;
        }

        if (!$subject instanceof Event) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }
        $uid = $user->getUid();
    
        if ($this->decisionManager->decide($token, [User::ROLE_ADMIN_EVENT_GLOBAL])) {
            switch ($attribute) {
                case self::CLOUD_ACCESS_TEAM:
                case self::CLOUD_ACCESS_MANAGEMENT:
                    return $this->decisionManager->decide($token, [User::ROLE_CLOUD]);
                default:
                    //allow almost everything
                    return true;
            }
        } elseif (
            !$this->decisionManager->decide($token, [User::ROLE_ADMIN_EVENT])
            && $attribute !== self::CLOUD_ACCESS_TEAM
            && $attribute !== self::CLOUD_ACCESS_MANAGEMENT
        ) {
            //if not even this permission is granted, disallow all
            return false;
        }

        /** @var Event $event */
        $event           = $subject;
        $userAssignments = $event->getUserAssignments();
        $userAssignment  = null;
        /** @var EventUserAssignment $userAssignmentForCheck */
        foreach ($userAssignments as $userAssignmentForCheck) {
            if ($userAssignmentForCheck->getUser()->getUid() === $uid) {
                $userAssignment = $userAssignmentForCheck;
            }
        }

        if (!$userAssignment) {
            return false;
        }

        switch ($attribute) {
            case self::READ:
            case self::PARTICIPANTS_READ:
            case self::EMPLOYEES_READ:
                return $userAssignment->isAllowedToRead();
            case self::EDIT:
            case self::EMPLOYEES_EDIT:
                return $userAssignment->isAllowedToEdit();
            case self::PARTICIPANTS_EDIT:
                return $userAssignment->isAllowedToManageParticipants();
            case self::COMMENT_READ:
                return $userAssignment->isAllowedToReadComments();
            case self::COMMENT_ADD:
                return $userAssignment->isAllowedToComment();
            case self::CLOUD_ACCESS_TEAM:
                return $this->decisionManager->decide($token, [User::ROLE_CLOUD])
                       && $userAssignment->isAllowedCloudAccessTeam();
            case self::CLOUD_ACCESS_MANAGEMENT:
                return $this->decisionManager->decide($token, [User::ROLE_CLOUD])
                       && $userAssignment->isAllowedCloudAccessManagement();
        }

        return false;
    }
}