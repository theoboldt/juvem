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

class GalleryImageRepository extends EntityRepository
{

    /**
     * Find images for transmitted event
     *
     * @param Event $event
     * @return GalleryImage[]|array
     */
    public function findByEvent(Event $event)
    {
        return $this->getEntityManager()->getRepository(GalleryImage::class)->findBy(['event' => $event], ['recordedAt' => 'ASC']);
    }

}
