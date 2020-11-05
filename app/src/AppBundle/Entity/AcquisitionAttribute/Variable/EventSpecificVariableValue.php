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
 * @ORM\Table(name="event_variable_value")
 * @ORM\HasLifecycleCallbacks()
 */
class EventSpecificVariableValue implements EventSpecificVariableValueInterface
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event")
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="cascade")
     *
     * @var Event
     */
    protected $event;
    
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable", inversedBy="values")
     * @ORM\JoinColumn(name="vid", referencedColumnName="id", onDelete="cascade")
     *
     * @var EventSpecificVariable
     */
    protected $variable;
    
    /**
     * Value to use for this variable and event
     *
     * @ORM\Column(type="float", name="variable_value")
     * @var float|null
     */
    protected $value;
    
    /**
     * EventSpecificVariableValue constructor.
     *
     * @param Event $event
     * @param EventSpecificVariable $variable
     * @param float|null $value
     */
    public function __construct(Event $event, EventSpecificVariable $variable, ?float $value)
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
        if ($variable) {
            $variable->addValue($this);
        }
        return $this;
    }
    
    /**
     * @return float|null
     */
    public function getValue(): ?float
    {
        return $this->value;
    }
    
    /**
     * @param float|null $value
     * @return EventSpecificVariableValue
     */
    public function setValue(?float $value): EventSpecificVariableValue
    {
        $this->value = $value;
        return $this;
    }
    
    
}