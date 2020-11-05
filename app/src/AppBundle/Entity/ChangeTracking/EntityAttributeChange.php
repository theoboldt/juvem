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
 * EntityAttributeChange
 *
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnly()
 */
class EntityAttributeChange
{
    /**
     * Attribute name of attribute which changed
     *
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @var string
     */
    private $attribute;
    
    /**
     * Value of attribute before change happened
     *
     * @Serialize\Expose
     * @var string|null|int|float|array
     */
    private $before;
    
    /**
     * Value of attribute after change happened
     *
     * @Serialize\Expose
     * @var string|null|int|float|array
     */
    private $after;
    
    /**
     * EntityAttributeChange constructor.
     *
     * @param string $attribute
     * @param array|float|int|string|null $before
     * @param array|float|int|string|null $after
     */
    public function __construct(string $attribute, $before, $after)
    {
        $this->attribute = $attribute;
        $this->before    = $before;
        $this->after     = $after;
    }
    
    /**
     * @return string
     */
    public function getAttribute(): string
    {
        return $this->attribute;
    }
    
    /**
     * @return array|float|int|string|null
     */
    public function getBefore()
    {
        return $this->before;
    }
    
    /**
     * @return array|float|int|string|null
     */
    public function getAfter()
    {
        return $this->after;
    }
    
    
}