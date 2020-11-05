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

/**
 * ExportTemplateRepository
 */
class ExportTemplateRepository extends EntityRepository
{
    
    /**
     * Find all suitable for event
     *
     * @param Event $event
     * @return array
     */
    public function findSuitableForEvent(Event $event): array
    {
        return $this->findAllOrderedByTitle();
    }
    
    /**
     * Get amount of templates
     *
     * @return int
     */
    public function templateCount(): int
    {
        return $this->count([]);
    }
    
    /**
     * Fetch all ordered by title
     *
     * @return array
     */
    public function findAllOrderedByTitle()
    {
        return $this->getEntityManager()
                    ->createQuery(
                        'SELECT e FROM AppBundle:ExportTemplate e ORDER BY e.createdAt DESC, e.title ASC'
                    )
                    ->getResult();
    }
}
