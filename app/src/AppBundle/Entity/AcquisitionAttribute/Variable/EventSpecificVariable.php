<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\AcquisitionAttribute\Variable;

use AppBundle\Entity\Audit\SoftDeleteTrait;
use AppBundle\Entity\Event;
use AppBundle\Manager\Payment\PriceSummand\Formula\FormulaVariableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serialize;

/**
 * EventSpecificVariable
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AcquisitionAttribute\Variable\VariableRepository")
 * @ORM\Table(name="event_variable", indexes={@ORM\Index(name="deleted_at_idx", columns={"deleted_at"})})
 * @ORM\HasLifecycleCallbacks()
 */
class EventSpecificVariable implements FormulaVariableInterface
{
    use SoftDeleteTrait;
    
    const FORMULA_VARIABLE_PREFIX = 'eventSpecific';
    
    /**
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    protected $description;
    
    /**
     * Contains the values for different events for this variable
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariableValue", mappedBy="variable")
     */
    protected $values;
    
    /**
     * If specified, this value is used if there is no value specified for a event
     *
     * @ORM\Column(type="float", name="variable_value", nullable=true)
     * @var float|null
     */
    protected $defaultValue = null;
    
    /**
     * EventSpecificVariable constructor.
     *
     * @param string $description
     * @param float|null $defaultValue
     */
    public function __construct(string $description = '', ?float $defaultValue = null)
    {
        $this->description  = $description;
        $this->defaultValue = $defaultValue;
        $this->values       = new ArrayCollection();
    }
    
    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }
    
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * @param string $description
     * @return EventSpecificVariable
     */
    public function setDescription(string $description): EventSpecificVariable
    {
        $this->description = $description;
        return $this;
    }
    
    /**
     * Get value for transmitted event
     *
     * @param Event $event                   Desired event
     * @param bool $useDefaultIfNotSpecified If set to true and default is specified, default is provided
     * @return EventSpecificVariableValueInterface
     */
    public function getValue(Event $event, bool $useDefaultIfNotSpecified): EventSpecificVariableValueInterface
    {
        /** @var EventSpecificVariableValue $value */
        foreach ($this->values as $value) {
            if ($value->getEvent()->getEid() === $event->getEid()) {
                return $value;
            }
        }
        if ($useDefaultIfNotSpecified) {
            if ($this->hasDefaultValue()) {
                return new EventSpecificVariableDefaultValue($this);
            }
            
            throw new NoDefaultValueSpecifiedException($this);
        }
        throw new NoValueSpecifiedException($this);
    }
    
    /**
     * @return \Traversable
     */
    public function getValues(): \Traversable
    {
        return $this->values->getIterator();
    }
    
    /**
     * @param ArrayCollection $values
     * @return EventSpecificVariable
     */
    public function setValues(ArrayCollection $values): EventSpecificVariable
    {
        $this->values = $values;
        return $this;
    }
    
    /**
     * Add a value
     *
     * @param EventSpecificVariableValue $value
     * @return EventSpecificVariable
     */
    public function addValue(EventSpecificVariableValue $value): EventSpecificVariable
    {
        if (!$this->values->contains($value)) {
            $this->values->add($value);
            if ($value->getVariable() !== $this) {
                $value->setVariable($this);
            }
        }
        return $this;
    }
    
    /**
     * Remove a value
     *
     * @param EventSpecificVariableValue $value
     * @return EventSpecificVariable
     */
    public function removeValue(EventSpecificVariableValue $value): EventSpecificVariable
    {
        if ($this->values->contains($value)) {
            $this->values->removeElement($value);
            if ($value->getVariable() === $this) {
                $value->setVariable(null);
            }
        }
        return $this;
    }
    
    /***
     * Determine if this value exists
     *
     * @param EventSpecificVariableValue $value
     * @return bool
     */
    public function hasValue(EventSpecificVariableValue $value): bool
    {
        return $this->values->contains($value);
    }
    
    /**
     * @return float|null
     */
    public function getDefaultValue(): ?float
    {
        return $this->defaultValue;
    }
    
    /**
     * Determine if default is set
     *
     * @return bool
     */
    public function hasDefaultValue(): bool
    {
        return $this->defaultValue !== null;
    }
    
    /**
     * @param float|null $defaultValue
     * @return EventSpecificVariable
     */
    public function setDefaultValue(?float $defaultValue): EventSpecificVariable
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }
    
    
    /**
     * Get formula variable name
     *
     * @return string
     */
    public function getFormulaVariable()
    {
        return self::FORMULA_VARIABLE_PREFIX . $this->getId();
    }
    
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->getFormulaVariable();
    }
    
    /**
     * @inheritDoc
     */
    public function isNummeric(): bool
    {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function isBoolean(): bool
    {
        return false;
    }
}