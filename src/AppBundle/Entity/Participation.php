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
use AppBundle\Entity\AcquisitionAttribute\FilloutTrait;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesModifiedInterface;
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingComparableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingStorableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SupportsChangeTrackingInterface;
use AppBundle\Form\EntityHavingFilloutsInterface;
use AppBundle\Manager\Geo\AddressAwareInterface;
use AppBundle\Manager\Payment\PriceSummand\SummandCausableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serialize;

/**
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnly()
 * @ORM\Entity
 * @ORM\Table(name="participation")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ParticipationRepository")
 */
class Participation implements EventRelatedEntity, SummandCausableInterface, EntityHavingFilloutsInterface, EntityHavingPhoneNumbersInterface, SoftDeleteableInterface, AddressAwareInterface, ProvidesModifiedInterface, ProvidesCreatedInterface, SupportsChangeTrackingInterface, SpecifiesChangeTrackingStorableRepresentationInterface, SpecifiesChangeTrackingComparableRepresentationInterface, HumanInterface
{
    use HumanTrait, FilloutTrait, CreatedModifiedTrait, AddressTrait, CommentableTrait;
    use SoftDeleteTrait {
        setDeletedAt as traitSetDeletedAt;
    }

    /**
     * @ORM\Column(type="integer", name="pid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $pid;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event", inversedBy="participations", cascade={"all"})
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="cascade")
     *
     * @var Event
     */
    protected $event;

    /**
     * Person Salutation - keeping incorrect name in order to not require rename of column
     *
     * @ORM\Column(type="string", length=64, name="salution")
     * @Assert\NotBlank()
     */
    protected $salutation;

    /**
     * @ORM\Column(type="string", length=128, name="email")
     * @Assert\NotBlank()
     */
    protected $email;

    /**
     * Contains the phone numbers assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\PhoneNumber", cascade={"all"}, mappedBy="participation")
     * @Assert\Valid()
     * @Assert\Count(
     *      min = "1",
     *      minMessage = "Bei einer Anmeldung muss mindestens eine Telefonnummer angegeben werden, unter der wir Sie in
     *      Notfällen erreichen können. Bitte fügen Sie mindestens noch eine Telefonnummer hinzu."
     * )
     */
    protected $phoneNumbers;

    /**
     * Contains the participants assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Participant", cascade={"all"}, mappedBy="participation")
     * @Assert\Valid()
     * @Assert\Count(
     *      min = "1",
     *      minMessage = "Bei einer Anmeldung muss mindestens ein Teilnehmer angegeben werden. Bitte fügen Sie noch
     *      mindestens einen Teilnehmer hinzu."
     * )
     */
    protected $participants;

    /**
     * Contains the participants assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AcquisitionAttribute\Fillout", cascade={"all"}, mappedBy="participation")
     */
    protected $acquisitionAttributeFillouts;

    /**
     * {@inheritdoc}
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ParticipationComment", cascade={"all"}, mappedBy="participation")
     */
    protected $comments;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="assignedParticipations")
     * @ORM\JoinColumn(name="uid", referencedColumnName="uid", onDelete="SET NULL")
     */
    protected $assignedUser;

    /**
     * Contains all related invoices
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Invoice", cascade={"all"}, mappedBy="participation")
     */
    protected $invoices;

    /**
     * Constructor
     *
     * @param Event|null $event               If set, related event is automatically defined
     * @param bool       $omitPhoneNumberInit If set to false, a single empty phone number is added
     * @param bool       $omitParticipantInit If set to false, a single empty participant is added
     */
    public function __construct(
        Event $event = null,
        bool $omitPhoneNumberInit = false,
        bool $omitParticipantInit = false
    ) {
        if ($event) {
            $this->setEvent($event);
        }

        $this->phoneNumbers = new ArrayCollection();
        if (!$omitPhoneNumberInit) {
            $phoneNumber = new PhoneNumber();
            $this->addPhoneNumber($phoneNumber);
        }

        $this->participants = new ArrayCollection();
        if (!$omitParticipantInit) {
            $participant = new Participant($this);
            $this->addParticipant($participant);
        }

        $this->acquisitionAttributeFillouts = new ArrayCollection();

        $this->modifiedAt = new \DateTime();
        $this->createdAt  = new \DateTime();
    }

    /**
     * Print the name, including first name if available
     *
     * @return string
     */
    public function getName()
    {
        $name = $this->getNameLast();

        if ($this->getNameFirst()) {
            $name .= ', ' . $this->getNameFirst();
        }

        return $name;
    }

    /**
     * Set deletedAt, cascading to participants
     *
     * @param \DateTime $deletedAt
     *
     * @return Participation
     */
    public function setDeletedAt($deletedAt)
    {
        $this->traitSetDeletedAt($deletedAt);

        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $participant->setDeletedAt($deletedAt);
        }

        return $this;
    }

    /**
     * Get pid
     *
     * @return integer
     */
    public function getPid()
    {
        return $this->pid;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->getPid();
    }

    /**
     * Set salutation
     *
     * @param string $parentSalutation Salutation
     *
     * @return Participation
     */
    public function setSalutation($parentSalutation)
    {
        $this->salutation = $parentSalutation;

        return $this;
    }

    /**
     * Get salutation
     *
     * @return string
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Participation
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get phone Numbers
     */
    public function getPhoneNumbers()
    {
        return $this->phoneNumbers;
    }

    /**
     * Add phoneNumber
     *
     * @param PhoneNumber $phoneNumber
     *
     * @return Participation
     */
    public function addPhoneNumber(PhoneNumber $phoneNumber)
    {
        $this->phoneNumbers[] = $phoneNumber;
        $phoneNumber->setParticipation($this);

        return $this;
    }

    /**
     * Remove phoneNumber
     *
     * @param PhoneNumber $phoneNumber
     */
    public function removePhoneNumber(PhoneNumber $phoneNumber)
    {
        $this->phoneNumbers->removeElement($phoneNumber);
    }

    /**
     * Add participant
     *
     * @param \AppBundle\Entity\Participant $participant
     *
     * @return Participation
     */
    public function addParticipant(Participant $participant)
    {
        $this->participants[] = $participant;
        $participant->setParticipation($this);

        return $this;
    }

    /**
     * Remove participant
     *
     * @param \AppBundle\Entity\Participant $participant
     */
    public function removeParticipant(Participant $participant)
    {
        $this->participants->removeElement($participant);
    }

    /**
     * Add invoice
     *
     * @param \AppBundle\Entity\Invoice $invoice
     *
     * @return Participation
     */
    public function addInvoice(Invoice $invoice)
    {
        $this->invoices[] = $invoice;
        $invoice->setParticipation($this);

        return $this;
    }

    /**
     * Remove invoice
     *
     * @param \AppBundle\Entity\Invoice $invoice
     */
    public function removeInvoice(Invoice $invoice)
    {
        $this->invoices->removeElement($invoice);
    }

    /**
     * Get participants
     *
     * @return array|Participant[]
     */
    public function getParticipants(): array
    {
        if ($this->participants instanceof Collection) {
            $participants = $this->participants->toArray();
        } else {
            $participants = $this->participants;
        }
        usort($participants, function(Participant $a, Participant $b) {
            return $a->getNameFirst() <=> $b->getNameFirst();
        });
        return $participants;
    }

    /**
     * Get list of participants aids
     *
     * @return array|int[]
     */
    public function getParticipantsIdList() {
        $ids = [];
        /** @var Participant $participant */
        foreach($this->participants as $participant) {
            $ids[] = $participant->getAid();
        }
        return $ids;
    }

    /**
     * Get price for all related participants
     *
     * @param bool $inEuro If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return int|double|null
     */
    public function getPrice($inEuro = false)
    {
        $price = null;

        /** @var Participant $participant */
        foreach ($this->participants as $participant) {
            $participantPrice = $participant->getBasePrice();
            if ($participantPrice !== null) {
                $price += $participantPrice;
            }
        }

        if ($price === null) {
            return null;
        } else {
            return $inEuro ? $price / 100 : $price;
        }
    }

    /**
     * Set event
     *
     * @param Event $event
     *
     * @return Participation
     */
    public function setEvent(Event $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return Event
     */
    public function getEvent(): ?Event
    {
        return $this->event;
    }

    /**
     * Set assignedUser
     *
     * @param \AppBundle\Entity\User $assignedUser
     *
     * @return Participation
     */
    public function setAssignedUser(\AppBundle\Entity\User $assignedUser = null)
    {
        $this->assignedUser = $assignedUser;

        return $this;
    }

    /**
     * Get assignedUser
     *
     * @return \AppBundle\Entity\User|null
     */
    public function getAssignedUser()
    {
        return $this->assignedUser;
    }

    /**
     * Check if all related participants are confirmed
     *
     * @return bool
     */
    public function isConfirmed()
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if (!$status->has(ParticipantStatus::TYPE_STATUS_CONFIRMED)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get all different confirmation sent dates for all participants if applicable
     *
     * @return array|\DateTime[]
     */
    public function getAllDifferentConfirmationSentAt(): array
    {
        $dates = [];
        /** @var Participant $participant */
        foreach ($this->participants as $participant) {
            $sent = $participant->getConfirmationSentAt();
            if ($sent) {
                $dates[$sent->format('U')] = clone $sent;
            }
        }
        
        return array_values($dates);
    }


    /**
     * Set conformation value for all related participants
     *
     * @param  bool $confirmed New value
     * @return self
     */
    public function setIsConfirmed($confirmed = true)
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if ($confirmed) {
                $status->enable(ParticipantStatus::TYPE_STATUS_CONFIRMED);
            } else {
                $status->disable(ParticipantStatus::TYPE_STATUS_CONFIRMED);
            }
            $participant->setStatus($status->__toString());
        }
        return $this;
    }

    /**
     * Check if all related participants are withdrawn
     *
     * @return bool
     */
    public function isWithdrawn()
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if (!$status->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set conformation value for all related participants
     *
     * @param   bool $withdrawn New value
     * @return self
     */
    public function setIsWithdrawn($withdrawn = true)
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if ($withdrawn) {
                $status->enable(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
            } else {
                $status->disable(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
            }
            $participant->setStatus($status->__toString());
        }
        return $this;
    }

    /**
     * Check if all related participants are rejected
     *
     * @return bool
     */
    public function isRejected()
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if (!$status->has(ParticipantStatus::TYPE_STATUS_REJECTED)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set conformation value for all related participants
     *
     * @param   bool $rejected New value
     * @return self
     */
    public function setIsRejected($rejected = true)
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if ($rejected) {
                $status->enable(ParticipantStatus::TYPE_STATUS_REJECTED);
            } else {
                $status->disable(ParticipantStatus::TYPE_STATUS_REJECTED);
            }
            $participant->setStatus($status->__toString());
        }
        return $this;
    }

    /**
     * Create a new participation for transmitted event based on data of given participation
     *
     * @param Participation $participationPrevious Data template
     * @param Event $event                         Event this new participation should belong to
     * @param bool $copyPrivateFields              If set to true, also private acquisition data and user assignments
     *                                             are copied
     * @return Participation
     */
    public static function createFromTemplateForEvent(
        Participation $participationPrevious, Event $event, $copyPrivateFields = false
    )
    {
        $participation = new self($event, true, true);
        $participation->setNameLast($participationPrevious->getNameLast());
        $participation->setNameFirst($participationPrevious->getNameFirst());
        $participation->setAddressCity($participationPrevious->getAddressCity());
        $participation->setAddressStreet($participationPrevious->getAddressStreet());
        $participation->setAddressZip($participationPrevious->getAddressZip());
        $participation->setAddressCountry($participationPrevious->getAddressCountry());
        $participation->setEmail($participationPrevious->getEmail());
        $participation->setSalutation($participationPrevious->getSalutation());

        if ($copyPrivateFields) {
            $participation->setAssignedUser($participationPrevious->getAssignedUser());
        }

        /** @var PhoneNumber $numberPrevious */
        foreach ($participationPrevious->getPhoneNumbers() as $numberPrevious) {
            $number = new PhoneNumber();
            $number->setParticipation($participation);
            $number->setDescription($numberPrevious->getDescription());
            $number->setNumber($numberPrevious->getNumber());
            $participation->addPhoneNumber($number);
        }

        /** @var Attribute $attribute */
        foreach ($event->getAcquisitionAttributes(true, false, false, $copyPrivateFields, true) as $attribute) {
            try {
                $filloutPrevious = $participationPrevious->getAcquisitionAttributeFillout($attribute->getBid(), false);
            } catch (\OutOfBoundsException $e) {
                continue;
            }
            $fillout = new Fillout();
            $fillout->setParticipation($participation);
            $fillout->setAttribute($attribute);
            $fillout->setValue($filloutPrevious->getRawValue());
            $participation->addAcquisitionAttributeFillout($fillout);
        }

        /** @var Participant $participantPrevious */
        foreach ($participationPrevious->getParticipants() as $participantPrevious) {
            $participant = new Participant($participation);
            $participant->setNameLast($participantPrevious->getNameLast());
            $participant->setNameFirst($participantPrevious->getNameFirst());
            $participant->setBirthday($participantPrevious->getBirthday());
            $participant->setFood($participantPrevious->getFood());
            $participant->setGender($participantPrevious->getGender());
            $participant->setInfoGeneral($participantPrevious->getInfoGeneral());
            $participant->setInfoMedical($participantPrevious->getInfoMedical());

            /** @var Attribute $attribute */
            foreach ($event->getAcquisitionAttributes(false, true, false, $copyPrivateFields, true) as $attribute) {
                try {
                    $filloutPrevious = $participantPrevious->getAcquisitionAttributeFillout(
                        $attribute->getBid(), false
                    );
                } catch (\OutOfBoundsException $e) {
                    continue;
                }
                $fillout = new Fillout();
                $fillout->setParticipant($participant);
                $fillout->setAttribute($attribute);
                $fillout->setValue($filloutPrevious->getRawValue());
                $participant->addAcquisitionAttributeFillout($fillout);
            }

            if ($copyPrivateFields) {
                $participant->setStatus($participantPrevious->getStatus());
            }

            $participation->addParticipant($participant);
        }

        return $participation;
    }
    
    /**
     * @inheritDoc
     */
    public function getComparableRepresentation()
    {
        return $this->getPid();
    }
    
    /**
     * @inheritDoc
     */
    public function getChangeTrackingStorableRepresentation()
    {
        return sprintf('%s @ %s [%d]', $this->getNameLast(), $this->event->getTitle(), $this->getPid());
    }
    
    /**
     * @inheritDoc
     */
    public static function getExcludedAttributes(): array
    {
        return ['comments', 'invoices'];
    }
    
}
