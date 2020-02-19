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

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventUserAssignment;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;


class AttributeVoter extends AbstractDecisionManagerAwareVoter
{
    const READ = 'read';
    const EDIT = 'edit';
    
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
            ]
        )) {
            return false;
        }
        
        if (!$subject instanceof Attribute) {
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
        if (!$this->decisionManager->decide($token, [User::ROLE_ADMIN])) {
            return false; //only admins allowed
        }
        
        switch ($attribute) {
            case self::READ:
            case self::EDIT:
                return $this->decisionManager->decide($token, [User::ROLE_ADMIN_EVENT]);
        }
        
        return false;
    }
}