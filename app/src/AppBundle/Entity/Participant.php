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

use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AttendanceList\AttendanceListParticipantFillout;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesModifiedInterface;
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingAttributeConvertersInterface;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingComparableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingStorableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SupportsChangeTrackingInterface;
use AppBundle\Entity\CustomField\CustomFieldValueCollection;
use AppBundle\Entity\CustomField\CustomFieldValueTrait;
use AppBundle\Entity\CustomField\EntityHavingCustomFieldValueInterface;
use AppBundle\Manager\Payment\PriceSummand\SummandImpactedInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnlyProperty()
 * @ORM\Entity
 * @ORM\Table(name="participant")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 */
class Participant implements EventRelatedEntity, SummandImpactedInterface, SoftDeleteableInterface, ProvidesModifiedInterface, ProvidesCreatedInterface, SupportsChangeTrackingInterface, SpecifiesChangeTrackingStorableRepresentationInterface, SpecifiesChangeTrackingComparableRepresentationInterface, SpecifiesChangeTrackingAttributeConvertersInterface, HumanInterface, EntityHavingCustomFieldValueInterface
{
    use HumanTrait, CreatedModifiedTrait, SoftDeleteTrait, CommentableTrait, BirthdayTrait;
    use CustomFieldValueTrait;
    
    const LABEL_GENDER_MALE         = 'männlich';
    const LABEL_GENDER_MALE_ALIKE   = 'divers, eher männlich';
    const LABEL_GENDER_DIVERSE      = 'divers';
    const LABEL_GENDER_FEMALE_ALIKE = 'divers, eher weiblich';
    const LABEL_GENDER_FEMALE       = 'weiblich';
    const LABEL_GENDER_OTHER        = 'anderes';

    /**
     * @ORM\Column(type="integer", name="aid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $aid;

    /**
     * @ORM\Column(type="string", length=128, name="name_first")
     * @Assert\NotBlank()
     */
    protected $nameFirst;

    /**
     * @ORM\ManyToOne(targetEntity="Participation", inversedBy="participants")
     * @ORM\JoinColumn(name="pid", referencedColumnName="pid", onDelete="cascade")
     */
    protected $participation;

    /**
     * @var string
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    protected $gender = '';

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank()
     * @Assert\Type("\DateTime")
     */
    protected $birthday;

    /**
     * @ORM\Column(type="text", name="info_medical")
     */
    protected $infoMedical = '';

    /**
     * @ORM\Column(type="text", name="info_general")
     */
    protected $infoGeneral = '';

    /**
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     */
    protected $status = 0;

    /**
     * Contains the price for this participant, in EURO CENT (instead of euro)
     *
     * @ORM\Column(type="integer", options={"unsigned":true}, nullable=true, name="price")
     */
    protected $basePrice = null;

    /**
     * Contains the list of attendance lists fillouts of this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AttendanceList\AttendanceListParticipantFillout", mappedBy="participant", cascade={"all"})
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $attendanceListsFillouts;

    /**
     * Contains the comments assigned
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ParticipantComment", cascade={"all"}, mappedBy="participant")
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $comments;

    /**
     * Contains all payment events related to this participant
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ParticipantPaymentEvent", cascade={"all"}, mappedBy="participant")
     */
    protected $paymentEvents;

    /**
     * Stores the date information about when the confirmation notification was sent the last time
     *
     * @var \DateTime|null
     * @ORM\Column(type="datetime", name="confirmation_sent_at", nullable=true)
     * @Serialize\Expose
     * @Serialize\Type("DateTime<'d.m.Y H:i'>")
     */
    protected $confirmationSentAt = null;

    /**
     * Custom field value collection containing a list of {@see CustomFieldValueContainer}
     *
     * Stores array or {@see CustomFieldValueCollection} which can be generated from this array containing a list
     * of {@see CustomFieldValueContainer} identified by the related {@see Attribute} id. Is encoded to JSON
     * when entity is persisted to database
     * 
     * @var CustomFieldValueCollection|array
     * @ORM\Column(type="json", length=16777215, name="custom_field_values", nullable=true)
     */
    private $customFieldValues = [];

    /**
     * Constructor
     *
     * @param Participation|null $participation Related participation
     */
    public function __construct(Participation $participation = null)
    {
        if ($participation) {
            $this->setParticipation($participation);
            if ($participation->getEvent()) {
                $this->setBasePrice($participation->getEvent()->getPrice());
            }
        }

        $this->modifiedAt = new \DateTime();
        $this->createdAt  = new \DateTime();

        $this->attendanceListsFillouts = new ArrayCollection();
        $this->comments                = new ArrayCollection();
        $this->paymentEvents           = new ArrayCollection();
    }

    /**
     * Get aid
     *
     * @return int|null
     */
    public function getAid()
    {
        return $this->aid;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->getAid();
    }

    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return self
     */
    public function setGender(string $gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender(): string
    {
        return $this->gender;
    }

    /**
     * Get gender term
     * 
     * @param bool $includeArticle
     * @return string
     */
    public function getGenderTerm(bool $includeArticle): string
    {
        $term = '';
        switch ($this->getGender()) {
            case Participant::LABEL_GENDER_FEMALE:
            case Participant::LABEL_GENDER_FEMALE_ALIKE:
                $term .= $includeArticle ? 'Die ' : '';
                $term .= 'Teilnehmerin';
                break;
            case Participant::LABEL_GENDER_MALE:
            case Participant::LABEL_GENDER_MALE_ALIKE:
                $term .= $includeArticle ? 'Der ' : '';
                $term .= 'Teilnehmer';
                break;
            default:
                $term .= $includeArticle ? 'Die ' : '';
                $term .= 'teilnehmende Person';
                break;
        }
        return $term;
    }

    /**
     * Set infoMedical
     *
     * @param string $infoMedical
     *
     * @return Participant
     */
    public function setInfoMedical($infoMedical)
    {
        if ($infoMedical === null || self::isInfoEmpty($infoMedical)) {
            //null comparison due to issue https://github.com/symfony/symfony/issues/5906
            $infoMedical = '';
        }
        $this->infoMedical = $infoMedical;

        return $this;
    }

    /**
     * Get infoMedical
     *
     * @return string
     */
    public function getInfoMedical()
    {
        return $this->infoMedical;
    }

    /**
     * Set infoGeneral
     *
     * @param string $infoGeneral
     *
     * @return Participant
     */
    public function setInfoGeneral($infoGeneral)
    {
        if ($infoGeneral === null || self::isInfoEmpty($infoGeneral)) {
            //null comparison due to issue https://github.com/symfony/symfony/issues/5906
            $infoGeneral = '';
        }

        $this->infoGeneral = $infoGeneral;

        return $this;
    }

    /**
     * Get infoGeneral
     *
     * @return string
     */
    public function getInfoGeneral()
    {
        return $this->infoGeneral;
    }

    /**
     * Set participation
     *
     * @param \AppBundle\Entity\Participation $participation
     *
     * @return Participant
     */
    public function setParticipation(Participation $participation = null)
    {
        $this->participation = $participation;

        return $this;
    }

    /**
     * Get participation
     *
     * @return \AppBundle\Entity\Participation
     */
    public function getParticipation()
    {
        return $this->participation;
    }


    /**
     * Get event from participation
     *
     * @return \AppBundle\Entity\Event|null
     */
    public function getEvent(): ?Event
    {
        $participation = $this->getParticipation();
        if (!$participation) {
            return null;
        }

        return $participation->getEvent();
    }

    /**
     * Set price
     *
     * @param int|double|null $price  Price for event in euro cents
     * @param bool            $inEuro If set to true, resulting price is returned in EURO instead of EURO CENT
     *
     * @return Participant
     */
    public function setBasePrice($price, $inEuro = false)
    {
        if ($inEuro) {
            $price = $price / 100;
        }

        $this->basePrice = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @param bool $inEuro If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return int|double|null
     */
    public function getBasePrice($inEuro = false)
    {
        if ($this->basePrice === null) {
            return null;
        } else {
            return $inEuro ? $this->basePrice / 100 : $this->basePrice;
        }
    }

    /**
     * Set status
     *
     * @param integer|ParticipantStatus $status
     *
     * @return Participant
     */
    public function setStatus($status)
    {
        if ($status instanceof ParticipantStatus) {
            $status = $status->getValue();
        }

        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @param bool $asMask Set to true to get value as mask
     * @return integer|ParticipantStatus
     */
    public function getStatus($asMask = false)
    {
        if ($asMask) {
            return new ParticipantStatus($this->status);
        }

        return $this->status;
    }

    /**
     * Check if this participant is confirmed
     *
     * @return bool
     */
    public function isConfirmed()
    {
        $status = $this->getStatus(true);
        return $status->has(ParticipantStatus::TYPE_STATUS_CONFIRMED);
    }

    /**
     * Check if this participant is withdrawn
     *
     * @return bool
     */
    public function isWithdrawn()
    {
        $status = $this->getStatus(true);
        return $status->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
    }

    /**
     * Check if there is withdraw requested for this participant
     *
     * @return bool
     */
    public function isWithdrawRequested()
    {
        $status = $this->getStatus(true);
        return $status->has(ParticipantStatus::TYPE_STATUS_WITHDRAW_REQUESTED);
    }

    /**
     * Set this participant as withdrawn
     *
     * @param   bool $withdrawn New value
     * @return self
     */
    public function setIsWithdrawn($withdrawn = true)
    {
        $status = $this->getStatus(true);
        if ($withdrawn) {
            $status->enable(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
        } else {
            $status->disable(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
        }
        return $this->setStatus($status);
    }

    /**
     * Mark withdraw requested for this participant
     *
     * @param   bool $withdrawn New value
     * @return self
     */
    public function setIsWithdrawRequested($withdrawn = true)
    {
        $status = $this->getStatus(true);
        if ($withdrawn) {
            $status->enable(ParticipantStatus::TYPE_STATUS_WITHDRAW_REQUESTED);
        } else {
            $status->disable(ParticipantStatus::TYPE_STATUS_WITHDRAW_REQUESTED);
        }
        return $this->setStatus($status);
    }

    /**
     * Check if this participant is rejected
     *
     * @return bool
     */
    public function isRejected()
    {
        $status = $this->getStatus(true);
        return $status->has(ParticipantStatus::TYPE_STATUS_REJECTED);
    }

    /**
     * Set this participant as rejected
     *
     * @param   bool $rejected New value
     * @return self
     */
    public function setIsRejected($rejected = true)
    {
        $status = $this->getStatus(true);
        if ($rejected) {
            $status->enable(ParticipantStatus::TYPE_STATUS_REJECTED);
        } else {
            $status->disable(ParticipantStatus::TYPE_STATUS_REJECTED);
        }
        return $this->setStatus($status);
    }

    /**
     * Add attendanceListsFillout
     *
     * @param AttendanceListParticipantFillout $attendanceListsFillout
     *
     * @return Participant
     */
    public function addAttendanceListsFillout(AttendanceListParticipantFillout $attendanceListsFillout)
    {
        $this->attendanceListsFillouts[] = $attendanceListsFillout;
        if ($attendanceListsFillout->getParticipant() !== $this) {
            $attendanceListsFillout->setParticipant($this);
        }
        
        return $this;
    }

    /**
     * Remove attendanceListsFillout
     *
     * @param AttendanceListParticipantFillout $attendanceListsFillout
     */
    public function removeAttendanceListsFillout(AttendanceListParticipantFillout $attendanceListsFillout)
    {
        $this->attendanceListsFillouts->removeElement($attendanceListsFillout);
    }

    /**
     * Get attendanceListsFillouts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttendanceListsFillouts()
    {
        return $this->attendanceListsFillouts;
    }

    /**
     * Add payment
     *
     * @param ParticipantPaymentEvent $event
     *
     * @return self
     */
    public function addPaymentEvent(ParticipantPaymentEvent $event)
    {
        $event->setParticipant($this);
        $this->paymentEvents->add($event);

        return $this;
    }

    /**
     * Remove payment event
     *
     * @param ParticipantPaymentEvent $event
     * @return self
     */
    public function removePaymentEvent(ParticipantPaymentEvent $event)
    {
        $this->paymentEvents->removeElement($event);
        return $this;
    }

    /**
     * Determine if the value of an info field is considered as empty
     *
     * @param string|null $value Value to check
     * @return bool              True if regarded as empty, false if not
     */
    public static function isInfoEmpty(string $value = null): bool
    {
        $acceptedAsEmpty = [
            'keine',
            '- keine -',
            'keine allergien',
            'keine besonderheiten',
            'nichts',
            '-',
            '--',
            'n/a',
            'nix',
            'nichts bekannt',
            'nichts bekannt!',
        ];
        return empty($value) || in_array(trim(mb_strtolower($value)), $acceptedAsEmpty);
    }
    
    /**
     * Get date information about when the confirmation notification was sent the last time
     *
     * @return \DateTime|null
     */
    public function getConfirmationSentAt(): ?\DateTime
    {
        return $this->confirmationSentAt;
    }
    
    /**
     * Set date information about when the confirmation notification was sent the last time
     *
     * @param \DateTime|null $confirmationSentAt
     */
    public function setConfirmationSentAt(?\DateTime $confirmationSentAt): void
    {
        $this->confirmationSentAt = $confirmationSentAt;
    }
    
    /**
     * @inheritDoc
     */
    public function getComparableRepresentation()
    {
        return $this->getAid();
    }
    
    /**
     * @inheritDoc
     */
    public function getChangeTrackingStorableRepresentation()
    {
        return sprintf('%s @ %s [%d]', $this->fullname(), $this->getEvent()->getTitle(), $this->getAid());
    }
    
    /**
     * @inheritDoc
     */
    public static function getExcludedAttributes(): array
    {
        return ['comments', 'paymentEvents', 'basePrice'];
    }
    
    /**
     * @inheritDoc
     */
    public function getChangeTrackingAttributeConverters(): array
    {
        return [
            'status' => function ($value) {
                $status = new ParticipantStatus($value);
                return implode(', ', $status->getActiveList(true));
            }
        ];
    }

    /**
     * Create a new participant from template and add to transmitted participation
     *
     * @param Participant   $participantPrevious   Data template
     * @param Participation $participation         Participation where templated participant should be added to
     * @param bool          $copyPrivateFields     If set to true, also private acquisition data and user assignments
     *                                             are copied
     * @return Participant
     */
    public static function createFromTemplateForParticipation(
        Participant   $participantPrevious,
        Participation $participation,
        bool          $copyPrivateFields = false
    ): Participant {
        $event = $participation->getEvent();

        $participant = new Participant($participation);
        $participant->setNameLast($participantPrevious->getNameLast());
        $participant->setNameFirst($participantPrevious->getNameFirst());
        $participant->setBirthday($participantPrevious->getBirthday());
        $participant->setGender($participantPrevious->getGender());
        $participant->setInfoGeneral($participantPrevious->getInfoGeneral());
        $participant->setInfoMedical($participantPrevious->getInfoMedical());

        $previousCustomFieldValueCollection    = $participantPrevious->getCustomFieldValues();
        $participantCustomFieldValueCollection = $participant->getCustomFieldValues();
        /** @var Attribute $attribute */
        foreach ($event->getAcquisitionAttributes(false, true, false, $copyPrivateFields, true) as $attribute) {
            $previousCustomFieldValueContainer = $previousCustomFieldValueCollection->get($attribute->getBid());
            if ($previousCustomFieldValueContainer) {
                $participantCustomFieldValueCollection->add(clone $previousCustomFieldValueContainer);
            }
        }

        if ($copyPrivateFields) {
            $participant->setStatus($participantPrevious->getStatus());
        }

        $participation->addParticipant($participant);

        return $participant;
    }
}
