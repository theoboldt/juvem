<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Export;

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use Traversable;

/**
 * Class AttributeOptionExplanation
 *
 * @package AppBundle\Export
 */
class AttributeOptionExplanation implements \IteratorAggregate
{

    /**
     * Attribute this explanation is about
     *
     * @var Attribute
     */
    private $attribute;

    /**
     * Choices which need to be included in explanation
     *
     * @var array
     */
    private $choices = [];

    /**
     * AttributeOptionExplanation constructor.
     *
     * @param Attribute $attribute
     */
    public function __construct(Attribute $attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * Add option to list
     *
     * @param AttributeChoiceOption $option
     */
    public function register(AttributeChoiceOption $option)
    {
        if (!$this->contains($option)) {
            $this->choices[] = $option->getId();
        }
    }

    /**
     * Determine if option is contained
     *
     * @param AttributeChoiceOption $option
     * @return bool
     */
    public function contains(AttributeChoiceOption $option)
    {
        return in_array($option->getId(), $this->choices);
    }

    /**
     * Get @see \Generator for @see $choices
     *
     * @return \Generator
     */
    public function choices(): \Generator
    {
        foreach ($this->attribute->getChoiceOptions() as $choice) {
            if (in_array($choice->getId(), $this->choices)) {
                yield $choice;
            }
        }
    }

    /**
     * Explain all used options of this @see Attribute
     *
     * @return null|string
     */
    public function explain()
    {
        if (!count($this->choices)) {
            return null;
        }
        $options = [];

        /** @var AttributeChoiceOption $choice */
        foreach ($this->attribute->getChoiceOptions() as $choice) {
            if (in_array($choice->getId(), $this->choices)) {
                $options[] = sprintf(
                    '%s: &I%s&I', self::sanatize($choice->getShortTitle(true)),
                    self::sanatize($choice->getManagementTitle(true))
                );
            }
        }

        return sprintf('&B%s&B: %s', self::sanatize($this->attribute->getManagementTitle()), implode(', ', $options));
    }

    /**
     * @param $text
     * @return mixed
     */
    private static function sanatize($text)
    {
        return str_replace('&', '+', $text);
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->explain();
    }

    /**
     * Get management title of @see Attribute
     *
     * @return string
     */
    public function getAttributeName()
    {
        return $this->attribute->getManagementTitle();
    }

    /**
     * Get management description of @see Attribute
     *
     * @return string
     */
    public function getAttributeDescription()
    {
        return $this->attribute->getManagementDescription();
    }

    /**
     * Retrieve an external iterator
     *
     * @link  https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator(): \Traversable
    {
        return $this->choices();
    }
}
