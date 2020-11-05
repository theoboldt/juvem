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
 * @Serialize\ReadOnly()
 */
class EntityCollectionChange
{
    
    const OPERATION_INSERT = 'insert';
    const OPERATION_DELETE = 'delete';
    
    /**
     * Attribute name of attribute which changed
     *
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @var string
     */
    private $attribute;
    
    /**
     * Operation code, either {@see self::OPERATION_INSERT} or {@see self::OPERATION_DELETE}
     *
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @var string
     */
    private $operation;
    
    /**
     * Class name of related entity for later use
     *
     * @var string
     */
    private $relatedClassName;
    
    /**
     * ID of related item
     *
     * @var null|int
     */
    private $relatedId;
    
    
    /**
     * Textual Identifier for changed element
     *
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @var float|int|string|null
     */
    private $value;
    
    /**
     * EntityAttributeChange constructor.
     *
     * @param string $attribute
     * @param string $operation
     * @param string $relatedClassName
     * @param null|int $relatedId
     * @param int|mixed|string $value
     */
    public function __construct(
        string $attribute, string $operation, string $relatedClassName, ?int $relatedId, $value
    )
    {
        $this->attribute        = $attribute;
        $this->operation        = $operation;
        $this->value            = $value;
        $this->relatedClassName = $relatedClassName;
        $this->relatedId        = $relatedId;
    }
    
    
    /**
     * @return string
     */
    public function getAttribute(): string
    {
        return $this->attribute;
    }
    
    /**
     * Get operation
     *
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }
    
    /**
     * @return string
     */
    public function getRelatedClassName(): string
    {
        return $this->relatedClassName;
    }
    
    /**
     * @return null|int
     */
    public function getRelatedId(): ?int
    {
        return $this->relatedId;
    }
    
    /**
     * @return array|float|int|string|null
     */
    public function getValue()
    {
        return $this->value;
    }
    
}