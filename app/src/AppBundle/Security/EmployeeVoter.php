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

use AppBundle\Entity\Employee;
use AppBundle\Entity\EventRelatedEntity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EmployeeVoter extends AbstractDecisionManagerAwareVoter
{
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return $subject instanceof Employee
               && in_array($attribute, [EventVoter::READ, EventVoter::EDIT]);
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
