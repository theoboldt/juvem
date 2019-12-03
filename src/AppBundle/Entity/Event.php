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

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AttendanceList\AttendanceList;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JMS\Serializer\Annotation as Serialize;

/**
 * @Vich\Uploadable
 * @ORM\Entity
 * @ORM\Table(name="event")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EventRepository")
 */
class Event implements SoftDeleteableInterface
{
    use CreatedModifiedTrait, SoftDeleteTrait, AddressTrait;

    const DATE_FORMAT_DATE      = 'd.m.y';
    const DATE_FORMAT_TIME      = 'H:i';
    const DATE_FORMAT_DATE_TIME = 'd.m.y H:i';

    const DEFAULT_COUNTRY = 'Deutschland';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $eid;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    protected $description;

    /**
     * @ORM\Column(type="text", length=160, name="description_meta", nullable=true)
     * @Assert\Length(
     *      max = 160,
     *      maxMessage = "Die Meta-Beschreibung darf nicht mehr als {{ limit }} Zeichen umfassen"
     * )
     */
    protected $descriptionMeta;

    /**
     * @ORM\Column(type="text", name="confirmation_message", nullable=true)
     */
    protected $confirmationMessage = null;

    /**
     * @ORM\Column(type="date", name="start_date")
     * @Assert\NotBlank()
     * @Assert\Type("\DateTime")
     */
    protected $startDate;

    /**
     * Defines the start time of the event. May be null for so called full day events
     *
     * @ORM\Column(type="time", name="start_time", nullable=true)
     * @Assert\Type("\DateTime")
     */
    protected $startTime;

    /**
     * @ORM\Column(type="date", name="end_date", nullable=true)
     * @Assert\Type("\DateTime")
     */
    protected $endDate;

    /**
     * @ORM\Column(type="time", name="end_time", nullable=true)
     * @Assert\Type("\DateTime")
     */
    protected $endTime;

    /**
     * @ORM\Column(type="boolean", name="is_active")
     */
    protected $isActive;

    /**
     * @ORM\Column(type="boolean", name="is_visible")
     */
    protected $isVisible;

    /**
     * @ORM\Column(type="boolean", name="is_auto_confirm")
     */
    protected $isAutoConfirm;

    /**
     * @Assert\Regex(
     *     pattern="/(\d+-\d+)|(\d[-]{0,1})|([-]{0,1}\d)|/",
     *     match=true,
     *     message="Wenn eine Altersspanne angegeben werden soll, bitte mit Bindestrich ohne Leerzeichen angeben"
     * )
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    protected $ageRange;

    /**
     * Contains the events price, in EURO CENT (instead of euro)
     *
     * @ORM\Column(type="integer", options={"unsigned":true}, nullable=true)
     */
    protected $price;

    /**
     * @ORM\Column(type="string", length=128, name="address_title", nullable=true)
     */
    protected $addressTitle;

    /**
     * @ORM\Column(type="string", length=128, name="address_street", nullable=true)
     */
    protected $addressStreet;

    /**
     * @ORM\Column(type="string", length=128, name="address_city", nullable=true)
     */
    protected $addressCity;

    /**
     * @ORM\Column(type="string", length=16, name="address_zip", nullable=true)
     */
    protected $addressZip;

    /**
     * @Vich\UploadableField(mapping="event_image", fileNameProperty="imageFilename")
     *
     * @var File|null
     */
    private $imageFile;

    /**
     * @ORM\Column(type="string", length=255, name="image_filename", nullable=true)
     *
     * @var string|null
     */
    private $imageFilename;
    
    /**
     * @Vich\UploadableField(mapping="invoice_template", fileNameProperty="invoiceTemplateFilename")
     * @Assert\Type(type="Symfony\Component\HttpFoundation\File\File")
     * @Assert\File(
     *     maxSize="5M",
     *     mimeTypes={"application/vnd.openxmlformats-officedocument.wordprocessingml.document"},
     *     mimeTypesMessage="Eine Rechnungsvorlage muss vom Typ Open XML Format (DOCX) sein"
     * )
     * @var Vich\UploadableField|File|null
     */
    private $invoiceTemplateFile;

    /**
     * @ORM\Column(type="string", length=255, name="invoice_template_filename", nullable=true)
     *
     * @var string|null
     */
    private $invoiceTemplateFilename;
    
    /**
     * Contains the acquisition attributes assigned to this event
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\AcquisitionAttribute\Attribute", inversedBy="events")
     * @ORM\JoinTable(name="event_acquisition_attribute",
     *      joinColumns={@ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="bid", referencedColumnName="bid",
     *      onDelete="CASCADE")}
     * )
     */
    protected $acquisitionAttributes;

    /**
     * Contains the participations assigned to this event
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Participation", mappedBy="event", cascade={"remove"})
     */
    protected $participations;

    /**
     * Contains the employees
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Employee", mappedBy="event", cascade={"remove"})
     */
    protected $employees;

    /**
     * Contains the gallery images assigned to this event
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\GalleryImage", mappedBy="event", cascade={"remove"})
     */
    protected $galleryImages;

    /**
     * Set to true if gallery link sharing is enabled
     *
     * @ORM\Column(type="boolean", name="is_gallery_link_sharing")
     * @var bool
     */
    protected $isGalleryLinkSharing = false;

    /**
     * Contains the list of attendance lists assigned to the event
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\AttendanceList\AttendanceList", mappedBy="event", cascade={"remove"})
     * @var Collection
     */
    protected $attendanceLists;

    /**
     * Able to store the amount of participations which are not withdrawn nor deleted
     *
     * @var int|null
     */
    protected $participantsCount = null;

    /**
     * Able to store the amount of participations are not withdrawn nor deleted but confirmed
     *
     * @var int|null
     */
    protected $participantsConfirmedCount = null;

    /**
     * @var Collection|User[]
     *
     * @ORM\ManyToMany(targetEntity="User", mappedBy="subscribedEvents")
     */
    protected $subscribers;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\EventUserAssignment", mappedBy="event", cascade={"persist",
     *                                                                     "remove"})
     * @var Collection|EventUserAssignment[]
     */
    protected $userAssignments;

    /**
     * If defined and number of participants is equal or greater than threadshold, waiting list warning is displayed
     *
     * @var int
     * @ORM\Column(type="integer", options={"unsigned":true}, nullable=true)
     */
    protected $waitingListThreshold = null;

    /**
     * Configuration for specific age filter, defines specific age
     *
     * @var int|null
     * @ORM\Column(type="integer", name="specific_age", options={"unsigned":true}, nullable=true)
     */
    protected $specificAge = null;

    /**
     * Configuration for specific age at date filter, defines specific date
     *
     * @var \DateTime|null
     * @ORM\Column(type="date", name="specific_date", nullable=true)
     * @Assert\Type("\DateTime")
     */
    protected $specificDate = null;

    /**
     * CONSTRUCTOR
     */
    public function __construct()
    {
        $this->employees             = new ArrayCollection();
        $this->participations        = new ArrayCollection();
        $this->galleryImages         = new ArrayCollection();
        $this->acquisitionAttributes = new ArrayCollection();
        $this->subscribers           = new ArrayCollection();
        $this->userAssignments       = new ArrayCollection();
    }

    /**
     * Get eid
     *
     * @return integer
     */
    public function getEid()
    {
        return $this->eid;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Event
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Event
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Event
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set startTime
     *
     * @param \DateTime $startTime
     *
     * @return Event
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Returns true if a start time is set
     *
     * @param  bool|null $value Value which not actually processed
     * @return bool
     */
    public function hasStartTime($value = null)
    {
        return (bool)$this->startTime;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return Event
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Returns true if a end date is set
     *
     * @param  bool|null $value Value which not actually processed
     * @return bool
     */
    public function hasEndDate($value = null)
    {
        return (bool)$this->endDate;
    }


    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     *
     * @return Event
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Returns true if a end time is set
     *
     * @param  bool|null $value Value which not actually processed
     * @return bool
     */
    public function hasEndTime($value = null)
    {
        return (bool)$this->endTime;
    }


    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Event
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * Set isVisible
     *
     * @param boolean $isVisible
     *
     * @return Event
     */
    public function setIsVisible($isVisible)
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    /**
     * Get isVisible
     *
     * @return boolean
     */
    public function isVisible()
    {
        return $this->isVisible;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Get isVisible
     *
     * @return boolean
     */
    public function getIsVisible()
    {
        return $this->isVisible;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     */
    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->modifiedAt = new \DateTime('now');
        }
    }

    /**
     * @return File
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }
    
    /**
     * @param string $imageFilename
     */
    public function setImageFilename($imageFilename)
    {
        $this->imageFilename = $imageFilename;
    }

    /**
     * @return string
     */
    public function getImageFilename()
    {
        return $this->imageFilename;
    }
    
    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     */
    public function setInvoiceTemplateFile(File $template = null)
    {
        $this->invoiceTemplateFile = $template;

        if ($template) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->modifiedAt = new \DateTime('now');
        }
    }

    /**
     * @return File
     */
    public function getInvoiceTemplateFile()
    {
        return $this->invoiceTemplateFile;
    }
    
    /**
     * @return string|null
     */
    public function getInvoiceTemplateFilename(): ?string
    {
        return $this->invoiceTemplateFilename;
    }
    
    /**
     * @param string|null $invoiceTemplateFilename
     */
    public function setInvoiceTemplateFilename(?string $invoiceTemplateFilename): void
    {
        $this->invoiceTemplateFilename = $invoiceTemplateFilename;
    }

    
    /**
     * Add participation
     *
     * @param \AppBundle\Entity\Participation $participation
     *
     * @return Event
     */
    public function addParticipation(\AppBundle\Entity\Participation $participation)
    {
        $this->participations[] = $participation;

        return $this;
    }

    /**
     * Remove participation
     *
     * @param \AppBundle\Entity\Participation $participation
     */
    public function removeParticipation(\AppBundle\Entity\Participation $participation)
    {
        $this->participations->removeElement($participation);
    }

    /**
     * Get participations
     *
     * @return Collection
     */
    public function getParticipations()
    {
        return $this->participations;
    }

    /**
     * Add galleryImage
     *
     * @param GalleryImage $galleryImage
     *
     * @return Event
     */
    public function addGalleryImage(GalleryImage $galleryImage)
    {
        $this->galleryImages[] = $galleryImage;

        return $this;
    }

    /**
     * Remove galleryImage
     *
     * @param GalleryImage $galleryImage
     */
    public function removeGalleryImage(GalleryImage $galleryImage)
    {
        $this->galleryImages->removeElement($galleryImage);
    }

    /**
     * Get galleryImages
     *
     * @return Collection|GalleryImage[]
     */
    public function getGalleryImages()
    {
        return $this->galleryImages;
    }

    /**
     * @return mixed
     */
    public function isGalleryLinkSharing()
    {
        return $this->getIsGalleryLinkSharing();
    }

    /**
     * @return mixed
     */
    public function getIsGalleryLinkSharing()
    {
        return $this->isGalleryLinkSharing;
    }

    /**
     * @param mixed $isGalleryLinkSharing
     * @return Event
     */
    public function setIsGalleryLinkSharing($isGalleryLinkSharing)
    {
        $this->isGalleryLinkSharing = $isGalleryLinkSharing;
        return $this;
    }

    /**
     * Count @see Participant entites, which fulfill filter, assigned to this entity via @see Participation
     *
     * @param callable $filter Filter to check
     * @return int             Amount of participants
     */
    private function countParticipantsByFilter(callable $filter)
    {
        $count = 0;
        /** @var Participation $participation */
        foreach ($this->participations as $participation) {
            /** @var Participant $participant */
            $participations = $participation->getParticipants()->filter($filter);
            $count          += $participations->count();
        }

        return $count;
    }

    /**
     * Get value of amount of participations not withdrawn nor deleted
     *
     * @return int
     */
    public function getParticipantsCount()
    {
        if ($this->participantsCount === null) {
            $this->participantsCount = $this->countParticipantsByFilter(
                function (Participant $participant) {
                    return $participant->getParticipation()->getDeletedAt() === null
                           && $participant->getDeletedAt() === null
                           && !$participant->isRejected()
                           && !$participant->isWithdrawn();
                }
            );
        }

        return $this->participantsCount;
    }

    /**
     * Get value of amount of participations not withdrawn nor deleted but not confirmed
     *
     * @return int
     */
    public function getParticipantsConfirmedCount()
    {
        if ($this->participantsConfirmedCount === null) {
            $this->participantsConfirmedCount = $this->countParticipantsByFilter(
                function (Participant $participant) {
                    return $participant->getParticipation()->getDeletedAt() === null
                           && $participant->getDeletedAt() === null
                           && $participant->isConfirmed()
                           && !$participant->isRejected()
                           && !$participant->isWithdrawn();
                }
            );
        }

        return $this->participantsConfirmedCount;
    }

    /**
     * Get value of amount of participations not withdrawn nor deleted but confirmed
     *
     * @return int
     */
    public function getParticipantsUnconfirmedCount()
    {
        return $this->getParticipantsCount() - $this->getParticipantsConfirmedCount();
    }

    /**
     * Set value of amount of participations
     *
     * @param int $participantsCount Amount of participations not withdrawn nor deleted
     * @return Event
     */
    public function setParticipantsCounts(int $participantsCount): Event
    {
        $this->participantsCount = $participantsCount;
        return $this;
    }

    /**
     * Set value of amount of participations
     *
     * @param int $participantsConfirmedCount Amount of participations not withdrawn nor deleted but confirmed
     * @return Event
     */
    public function setParticipantsConfirmedCount(int $participantsConfirmedCount): Event
    {
        $this->participantsConfirmedCount = $participantsConfirmedCount;
        return $this;
    }

    /**
     * Add an acquisition attribute assignment to this event
     *
     * @param AcquisitionAttribute\Attribute $acquisitionAttribute
     *
     * @return Event
     */
    public function addAcquisitionAttribute(AcquisitionAttribute\Attribute $acquisitionAttribute)
    {
        $this->acquisitionAttributes[] = $acquisitionAttribute;

        return $this;
    }

    /**
     * Remove an acquisition attribute assignment from this event
     *
     * @param \AppBundle\Entity\AcquisitionAttribute\Attribute $acquisitionAttribute
     */
    public function removeAcquisitionAttribute(AcquisitionAttribute\Attribute $acquisitionAttribute)
    {
        $this->acquisitionAttributes->removeElement($acquisitionAttribute);
    }

    /**
     * Get acquisition attributes assigned to this event
     *
     * @param bool $includeParticipationFields If acquisition fields related to participations should be included
     * @param bool $includeParticipantFields   If acquisition fields related to participant should be included
     * @param bool $includeEmployeeFields
     * @param bool $includePrivate             If non-public fields should be included
     * @param bool $includePublic              If public fields should be included
     * @return ArrayCollection|array Result
     */
    public function getAcquisitionAttributes(
        bool $includeParticipationFields = true,
        bool $includeParticipantFields = true,
        bool $includeEmployeeFields = true,
        bool $includePrivate = true,
        bool $includePublic = true
    ) {
        if ($includeParticipationFields
            && $includeParticipantFields
            && $includeEmployeeFields
            && $includePublic
            && $includePrivate) {
            return $this->acquisitionAttributes;
        }
        $acquisitionAttributes = [];

        /** @var Attribute $acquisitionAttribute */
        foreach ($this->acquisitionAttributes as $acquisitionAttribute) {
            if (
                (
                    ($includeParticipationFields && $acquisitionAttribute->getUseAtParticipation()) ||
                    ($includeParticipantFields && $acquisitionAttribute->getUseAtParticipant()) ||
                    ($includeEmployeeFields && $acquisitionAttribute->getUseAtEmployee())
                )
                && (
                    ($includePublic && $acquisitionAttribute->isPublic()) ||
                    ($includePrivate && !$acquisitionAttribute->isPublic())
                )
            ) {
                $acquisitionAttributes[$acquisitionAttribute->getName()] = $acquisitionAttribute;
            }
        }

        return $acquisitionAttributes;
    }

    /**
     * Get acquisition attribute with given bid assigned to this event
     *
     * @param int $bid The id of the field
     * @return Attribute             The field
     * @throws  \OutOfBoundsException           If Requested field was not found
     */
    public function getAcquisitionAttribute($bid)
    {
        /** @var Attribute $acquisitionAttribute */
        foreach ($this->acquisitionAttributes as $acquisitionAttribute) {
            if ($acquisitionAttribute->getBid() == $bid) {
                return $acquisitionAttribute;
            }
        }
        throw new \OutOfBoundsException('Requested field was not found');
    }


    /**
     * Set confirmationMessage
     *
     * @param string $confirmationMessage
     *
     * @return Event
     */
    public function setConfirmationMessage($confirmationMessage)
    {
        $this->confirmationMessage = $confirmationMessage;

        return $this;
    }

    /**
     * Get confirmationMessage
     *
     * @param null $value Need to have $value parameter for form
     * @return string
     */
    public function hasConfirmationMessage($value = null)
    {
        return (bool)$this->confirmationMessage;
    }

    /**
     * Get confirmationMessage
     *
     * @return string
     */
    public function getConfirmationMessage()
    {
        return $this->confirmationMessage;
    }


    /**
     * Set isAutoConfirm
     *
     * @param boolean $isAutoConfirm
     *
     * @return Event
     */
    public function setIsAutoConfirm($isAutoConfirm)
    {
        $this->isAutoConfirm = $isAutoConfirm;

        return $this;
    }

    /**
     * Get isAutoConfirm
     *
     * @return boolean
     */
    public function getIsAutoConfirm()
    {
        return $this->isAutoConfirm;
    }

    /**
     * Add attendanceList
     *
     * @param AttendanceList $attendanceList
     *
     * @return Event
     */
    public function addAttendanceList(AttendanceList $attendanceList)
    {
        $this->attendanceLists[] = $attendanceList;

        return $this;
    }

    /**
     * Remove attendanceList
     *
     * @param AttendanceList $attendanceList
     */
    public function removeAttendanceList(AttendanceList $attendanceList)
    {
        $this->attendanceLists->removeElement($attendanceList);
    }

    /**
     * Get attendanceLists
     *
     * @return Collection
     */
    public function getAttendanceLists()
    {
        return $this->attendanceLists;
    }

    /**
     * Add subscriber
     *
     * @param User $subscriber
     * @return self
     */
    public function addSubscriber(User $subscriber)
    {
        if (!$this->subscribers->contains($subscriber)) {
            $this->subscribers->add($subscriber);
            $subscriber->addSubscribedEvent($this);
        }
        return $this;
    }

    /**
     * Remove subscriber
     *
     * @param User $subscriber
     * @return self
     */
    public function removeSubscriber(User $subscriber)
    {
        if ($this->subscribers->contains($subscriber)) {
            $this->subscribers->removeElement($subscriber);
            $subscriber->removeSubscribedEvent($this);
        }
        return $this;
    }

    /**
     * Get subscribers
     *
     * @return Collection|User[]
     */
    public function getSubscribers()
    {
        return $this->subscribers;
    }

    /**
     * Find out if event is subscribed by
     *
     * @param User $subscriber
     * @return bool
     */
    public function isSubscribedBy(User $subscriber)
    {
        return ($this->subscribers->contains($subscriber));
    }

    /**
     * @return EventUserAssignment[]|Collection
     */
    public function getUserAssignments()
    {
        return $this->userAssignments;
    }

    /**
     * Set ageRange
     *
     * @param string $ageRange
     *
     * @return Event
     */
    public function setAgeRange($ageRange)
    {
        $this->ageRange = $ageRange;

        return $this;
    }

    /**
     * Get ageRange
     *
     * @return string
     */
    public function getAgeRange()
    {
        return $this->ageRange;
    }

    /**
     * Set price
     *
     * @param string $price
     *
     * @return Event
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @param bool $inEuro  If set to true, resulting price is returned in EURO instead of EURO CENT
     * @return string
     */
    public function getPrice($inEuro = false)
    {
        return $inEuro ? $this->price/100 : $this->price;
    }

    /**
     * Set addressTitle
     *
     * @param string $addressTitle
     *
     * @return Event
     */
    public function setAddressTitle($addressTitle)
    {
        $this->addressTitle = $addressTitle;

        return $this;
    }

    /**
     * Get addressTitle
     *
     * @return string
     */
    public function getAddressTitle()
    {
        return $this->addressTitle;
    }

    /**
     * Set descriptionMeta
     *
     * @param string $descriptionMeta
     *
     * @return Event
     */
    public function setDescriptionMeta($descriptionMeta)
    {
        $this->descriptionMeta = $descriptionMeta;

        return $this;
    }

    /**
     * Get description used for meta tags and short descriptions
     *
     * @param bool $useDescriptionExcerptAsFallback If set to true and @see $descriptionMeta is empty an excerpt of
     *                                              @see $description is returned
     * @return string
     */
    public function getDescriptionMeta($useDescriptionExcerptAsFallback = false)
    {
        if ($useDescriptionExcerptAsFallback && !$this->descriptionMeta) {
            if (mb_strlen($this->description) > 156) {
                return mb_substr($this->description, 0, 154).'…';
            }
            return $this->descriptionMeta;
        }
        return $this->descriptionMeta;
    }

    /**
     * Defined threshold for waiting list
     *
     * @return int|null
     */
    public function getWaitingListThreshold()
    {
        return $this->waitingListThreshold;
    }

    /**
     * Determine if threshold for waiting list specified
     *
     * @param null $value Need to have $value parameter for form
     * @return bool
     */
    public function hasWaitingListThreshold($value = null)
    {
        return $this->waitingListThreshold !== null;
    }

    /**
     * Define threshold for waiting list
     *
     * @param int|null $waitingListThreshold
     * @return Event
     */
    public function setWaitingListThreshold(int $waitingListThreshold = null)
    {
        $this->waitingListThreshold = $waitingListThreshold;
        return $this;
    }

    /**
     * Get configuration for specific age filter, defines specific age
     *
     * @return int|null
     */
    public function getSpecificAge(): ?int
    {
        return $this->specificAge;
    }

    /**
     * Set configuration for specific age filter, defines specific age
     *
     * @param int|null $specificAge
     * @return Event
     */
    public function setSpecificAge(?int $specificAge): Event
    {
        $this->specificAge = $specificAge;
        return $this;
    }

    /**
     * Get configuration for specific age at date filter, defines specific date
     *
     * @return \DateTime|null
     */
    public function getSpecificDate(): ?\DateTime
    {
        return $this->specificDate;
    }

    /** Set configuration for specific age at date filter, defines specific date
     *
     * @param \DateTime|null $specificDate
     * @return Event
     */
    public function setSpecificDate(?\DateTime $specificDate): Event
    {
        $this->specificDate = $specificDate;
        return $this;
    }


    /**
     * Add employee
     *
     * @param Employee $employee
     *
     * @return Event
     */
    public function addEmployee(Employee $employee)
    {
        $this->employees[] = $employee;

        return $this;
    }

    /**
     * Remove employee
     *
     * @param Employee $employee
     */
    public function removeEmployee(Employee $employee)
    {
        $this->employees->removeElement($employee);
    }

    /**
     * Get employees
     *
     * @return array|Employee[]
     */
    public function getEmployees(): array
    {
        return $this->employees->toArray();
    }
}
