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

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serialize;

/**
 * EventSpecificVariable
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AcquisitionAttribute\Variable\VariableRepository")
 * @ORM\Table(name="acquisition_attribute_variable_event", indexes={@ORM\Index(name="deleted_at_idx", columns={"deleted_at"})})
 * @ORM\HasLifecycleCallbacks()
 */
class EventSpecificVariable
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\AcquisitionAttribute\Attribute", inversedBy="eventSpecificVariables", cascade={"all"}, fetch="EAGER")
     * @ORM\JoinColumn(name="bid", referencedColumnName="bid", onDelete="cascade")
     *
     * @var Attribute
     */
    protected $attribute;
    
    /**
     * Contains the values for different events for this variable
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariableValue", mappedBy="variable", cascade={"remove"})
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
     * @param Attribute $attribute
     * @param string $description
     * @param float|null $defaultValue
     */
    public function __construct(Attribute $attribute, string $description = '', ?float $defaultValue = null)
    {
        $this->attribute    = $attribute;
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
     * @return EventSpecificVariable
     */
    public function getAttribute(): EventSpecificVariable
    {
        return $this->attribute;
    }
    
    /**
     * @param EventSpecificVariable $attribute
     * @return EventSpecificVariable
     */
    public function setAttribute(EventSpecificVariable $attribute): EventSpecificVariable
    {
        $this->attribute = $attribute;
        return $this;
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
}