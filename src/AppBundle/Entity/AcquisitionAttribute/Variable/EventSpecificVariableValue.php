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
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serialize;
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Event;

/**
 * EventSpecificVariable
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AcquisitionAttribute\Variable\VariableRepository")
 * @ORM\Table(name="acquisition_attribute_variable_event_value", indexes={@ORM\Index(name="deleted_at_idx", columns={"deleted_at"})})
 * @ORM\HasLifecycleCallbacks()
 */
class EventSpecificVariableValue implements SoftDeleteableInterface
{
    use SoftDeleteTrait;
    
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event", cascade={"all"})
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="cascade")
     *
     * @var Event
     */
    protected $event;
    
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable", inversedBy="values", cascade={"all"})
     * @ORM\JoinColumn(name="vid", referencedColumnName="id", onDelete="cascade")
     *
     * @var EventSpecificVariable
     */
    protected $variable;
    
    /**
     * Value to use for this variable and event
     *
     * @ORM\Column(type="float", name="variable_value")
     * @var float
     */
    protected $value;
    
    /**
     * EventSpecificVariableValue constructor.
     *
     * @param Event $event
     * @param EventSpecificVariable $variable
     * @param float $value
     */
    public function __construct(Event $event, EventSpecificVariable $variable, float $value)
    {
        $this->event    = $event;
        $this->variable = $variable;
        $this->value    = $value;
    }
    
    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }
    
    /**
     * @param Event $event
     * @return EventSpecificVariableValue
     */
    public function setEvent(Event $event): EventSpecificVariableValue
    {
        $this->event = $event;
        return $this;
    }
    
    /**
     * @return null|EventSpecificVariable
     */
    public function getVariable(): ?EventSpecificVariable
    {
        return $this->variable;
    }
    
    /**
     * @param null|EventSpecificVariable $variable
     * @return EventSpecificVariableValue
     */
    public function setVariable(?EventSpecificVariable $variable): EventSpecificVariableValue
    {
        $this->variable = $variable;
        $variable->addValue($this);
        return $this;
    }
    
    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }
    
    /**
     * @param float $value
     * @return EventSpecificVariableValue
     */
    public function setValue(float $value): EventSpecificVariableValue
    {
        $this->value = $value;
        return $this;
    }
    
    
}