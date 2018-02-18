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

use AppBundle\Entity\Audit\CreatedTrait;
use AppBundle\Entity\Audit\CreatorTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="participant_payment_event")
 */
class ParticipantPaymentEvent
{
    use CreatedTrait, CreatorTrait;

    const EVENT_TYPE_SET           = 'price_set';
    const EVENT_TYPE_SET_LABEL     = 'Preis Festlegung';
    const EVENT_TYPE_PAYMENT       = 'price_payment';
    const EVENT_TYPE_PAYMENT_LABEL = 'Zahlungserfassung';

    /**
     * @ORM\Column(type="integer", name="yid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $yid;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description = null;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isPriceSet = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isPricePayment = true;

    /**
     * Contains the events value, in EURO CENT (instead of euro)
     *
     * @ORM\Column(type="integer", name="price_value")
     */
    protected $value;

    /**
     * @ORM\ManyToOne(targetEntity="Participant", inversedBy="paymentEvents")
     * @ORM\JoinColumn(name="aid", referencedColumnName="aid", onDelete="cascade")
     */
    protected $participant;

    /**
     * Create new payment event
     *
     * @param User|null   $createdBy   User who caused this event
     * @param int         $value       Value of event
     * @param string|null $description Description
     * @return ParticipantPaymentEvent
     */
    public static function createPaymentEvent(User $createdBy = null, int $value, string $description = null)
    {
        return new self($createdBy, self::EVENT_TYPE_PAYMENT, $value, $description);
    }

    /**
     * Create price set event
     *
     * @param User|null   $createdBy   User who caused this event
     * @param int         $value       Value of event
     * @param string|null $description Description
     * @return ParticipantPaymentEvent
     */
    public static function createPriceSetEvent(User $createdBy = null, int $value, string $description = null)
    {
        return new self($createdBy, self::EVENT_TYPE_SET, $value, $description);
    }

    /**
     * ParticipantPaymentEvent constructor.
     *
     * @param User|null   $createdBy   User who caused this event
     * @param string      $type        Event type
     * @param int         $value       Value of event
     * @param string|null $description Description
     */
    public function __construct(User $createdBy = null, string $type, int $value, string $description = null)
    {
        $this->createdAt   = new \DateTime();
        $this->createdBy   = $createdBy;
        $this->description = $description;
        $this->value       = $value;
        $this->setEventType($type);
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @param bool $inEuro If set to true, resulting value is returned in EURO instead of EURO CENT
     * @return int|float
     */
    public function getValue($inEuro = false)
    {
        return $inEuro ? $this->value / 100 : $this->value;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Define event type
     *
     * @param string $type
     * @return self
     */
    public function setEventType($type)
    {
        if (!self::isEventTypeValid($type)) {
            throw new \RuntimeException('Invalid event type');
        }
        switch ($type) {
            case self::EVENT_TYPE_PAYMENT:
                $this->isPricePayment = true;
                $this->isPriceSet     = false;
                break;
            case self::EVENT_TYPE_SET:
                $this->isPricePayment = false;
                $this->isPriceSet     = true;
                break;
        }
        return $this;
    }

    /**
     * Get the event type of this payment event
     *
     * @return string
     * @throws \RuntimeException If invalid payment event configuration present
     */
    public function getEventType()
    {
        if ($this->isPricePayment && !$this->isPriceSet) {
            return self::EVENT_TYPE_PAYMENT;
        } elseif (!$this->isPricePayment && $this->isPriceSet) {
            return self::EVENT_TYPE_SET;
        } else {
            throw new \RuntimeException('Invalid payment event configuration');
        }
    }

    /**
     * Determine if this is an payment event
     *
     * @return bool
     */
    public function isPricePaymentEvent() {
        return $this->getEventType() === self::EVENT_TYPE_PAYMENT;
    }

    /**
     * Determine if this is an price set event
     *
     * @return bool
     */
    public function isPriceSetEvent() {
        return $this->getEventType() === self::EVENT_TYPE_SET;
    }

    /**
     * Get label for event type of this payment event
     *
     * @return string
     * @throws \RuntimeException If invalid payment event configuration present
     */
    public function getEventTypeLabeled()
    {
        switch ($this->getEventType()) {
            case self::EVENT_TYPE_PAYMENT:
                return self::EVENT_TYPE_PAYMENT_LABEL;
                break;
            case self::EVENT_TYPE_SET:
                return self::EVENT_TYPE_SET_LABEL;
                break;
            default:
                throw new \RuntimeException('Invalid payment event configuration');
        }
    }

    /**
     * Check if transmitted event type is valid
     *
     * @param mixed $type Value to check
     * @return bool
     */
    public static function isEventTypeValid($type)
    {
        return in_array($type, [self::EVENT_TYPE_SET, self::EVENT_TYPE_PAYMENT]);
    }

    /**
     * @return Participant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * @param mixed $participant
     * @return ParticipantPaymentEvent
     */
    public function setParticipant($participant)
    {
        $this->participant = $participant;
        return $this;
    }
}
