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
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


class EventVoter extends Voter
{
    const READ              = 'read';
    const EDIT              = 'edit';
    const PARTICIPANTS_READ = 'participants_read';
    const PARTICIPANTS_EDIT = 'participants_edit';
    const COMMENT_READ      = 'comment_read';
    const COMMENT_ADD       = 'comment_add';

    /**
     * Decision manager
     *
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * EventVoter constructor.
     *
     * @param AccessDecisionManagerInterface $decisionManager
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

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
                self::COMMENT_READ,
                self::COMMENT_ADD,
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
            //allow everything
            return true;
        } elseif (!$this->decisionManager->decide($token, [User::ROLE_ADMIN_EVENT])) {
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
                return true;
            case self::EDIT:
                return $userAssignment->isAllowedToEdit();
            case self::PARTICIPANTS_EDIT:
                return $userAssignment->isAllowedToManageParticipants();
            case self::COMMENT_READ:
                return $userAssignment->isAllowedToReadComments();
            case self::COMMENT_ADD:
                return $userAssignment->isAllowedToComment();
        }

        return false;
    }
}