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
use AppBundle\Entity\EventRelatedEntity;
use AppBundle\Entity\EventUserAssignment;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;


class EventRelatedVoter extends AbstractDecisionManagerAwareVoter
{
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array(
            $attribute,
            [
                EventVoter::PARTICIPANTS_READ,
                EventVoter::PARTICIPANTS_EDIT,
                EventVoter::EMPLOYEES_READ,
                EventVoter::EMPLOYEES_EDIT,
                EventVoter::CLOUD_ACCESS_MANAGEMENT,
                EventVoter::CLOUD_ACCESS_TEAM,
            ]
        )) {
            return false;
        }
        
        if (!$subject instanceof EventRelatedEntity
            || !$subject->getEvent()
        ) {
            return false;
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var EventRelatedEntity $subject */
        return $this->decisionManager->decide($token, [$attribute], $subject->getEvent());
    }
}