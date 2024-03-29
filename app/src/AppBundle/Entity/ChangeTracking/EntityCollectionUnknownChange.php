<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\ChangeTracking;

use JMS\Serializer\Annotation as Serialize;

/**
 * EntityCollectionChange
 *
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnlyProperty()
 */
class EntityCollectionUnknownChange
{
    
    const OPERATION_INSERT = 'insert';
    const OPERATION_DELETE = 'delete';
    
}
