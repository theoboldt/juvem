<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class UserAttachmentRepository extends EntityRepository
{

    /**
     * Find attachments for transmitted user
     *
     * @param User $user
     * @return UserAttachment[]
     */
    public function findByUser(User $user)
    {
        return $this->getEntityManager()
                    ->getRepository(UserAttachment::class)
                    ->findBy(['user' => $user], ['filenameOriginal' => 'ASC', 'createdAt' => 'ASC']);
    }

}
