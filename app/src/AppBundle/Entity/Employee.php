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
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\FilloutTrait;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesModifiedInterface;
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingAttributeConvertersInterface;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingComparableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingStorableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SupportsChangeTrackingInterface;
use AppBundle\Form\EntityHavingFilloutsInterface;
use AppBundle\Manager\Geo\AddressAwareInterface;
use AppBundle\Manager\Payment\PriceSummand\SummandImpactedInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnly()
 * @ORM\Entity
 * @ORM\Table(name="employee")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EmployeeRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 */
class Employee implements EventRelatedEntity, EntityHavingFilloutsInterface, EntityHavingPhoneNumbersInterface, SummandImpactedInterface, SoftDeleteableInterface, AddressAwareInterface, ProvidesModifiedInterface, ProvidesCreatedInterface, SupportsChangeTrackingInterface, SpecifiesChangeTrackingStorableRepresentationInterface, SpecifiesChangeTrackingComparableRepresentationInterface, SpecifiesChangeTrackingAttributeConvertersInterface, HumanInterface
{
    use HumanTrait, FilloutTrait, CreatedModifiedTrait, AddressTrait, CommentableTrait;
    
    use SoftDeleteTrait {
        setDeletedAt as traitSetDeletedAt;
    }
    
    /**
     * @ORM\Column(type="integer", name="gid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $gid;
    
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event", inversedBy="employees", cascade={"all"})
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="cascade")
     *
     * @var Event
     */
    protected $event;
    
    /**
     * @ORM\Column(type="string", length=64, name="salutation")
     * @Assert\NotBlank()
     */
    protected $salutation;
    
    /**
     * @ORM\Column(type="string", length=128, name="email")
     * @Assert\NotBlank()
     */
    protected $email;
    
    /**
     * Contains the phone numbers assigned to this employee
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\PhoneNumber", cascade={"all"}, mappedBy="employee")
     */
    protected $phoneNumbers;
    
    /**
     * Contains the comments assigned
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\EmployeeComment", cascade={"all"}, mappedBy="employee")
     */
    protected $comments;
    
    /**
     * Contains the participants assigned to this employee
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AcquisitionAttribute\Fillout",
     *     cascade={"all"}, mappedBy="employee")
     */
    protected $acquisitionAttributeFillouts;
    
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="assignedEmployees")
     * @ORM\JoinColumn(name="uid", referencedColumnName="uid", onDelete="SET NULL")
     */
    protected $assignedUser;
    
    /**
     * @ORM\ManyToOne(targetEntity="Employee")
     * @ORM\JoinColumn(name="predecessor_gid", referencedColumnName="gid", onDelete="SET NULL")
     * @var Employee|null
     */
    protected $predecessor = null;
    
    /**
     * @ORM\Column(type="boolean", name="is_confirmed")
     */
    protected $isConfirmed = false;
    
    /**
     * Employee constructor.
     *
     * @param Event|null $event Related event
     */
    public function __construct(Event $event = null)
    {
        $this->acquisitionAttributeFillouts = new ArrayCollection();
        
        $this->phoneNumbers = new ArrayCollection();
        $this->comments     = new ArrayCollection();
        $this->modifiedAt   = new \DateTime();
        $this->createdAt    = new \DateTime();
        $this->event        = $event;
    }
    
    /**
     * Get employee id
     *
     * @return int|null
     */
    public function getGid(): ?int
    {
        return $this->gid;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->getGid();
    }

    /**
     * @return mixed
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * @param mixed $salutation
     */
    public function setSalutation($salutation): void
    {
        $this->salutation = $salutation;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     * @return Employee
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get phone Numbers
     *
     * @return array
     */
    public function getPhoneNumbers()
    {
        return $this->phoneNumbers->toArray();
    }

    /**
     * Add phoneNumber
     *
     * @param PhoneNumber $phoneNumber
     *
     * @return Employee
     */
    public function addPhoneNumber(PhoneNumber $phoneNumber)
    {
        if (!$this->phoneNumbers->contains($phoneNumber)) {
            $this->phoneNumbers->add($phoneNumber);
            $phoneNumber->setEmployee($this);
        }

        return $this;
    }

    /**
     * Remove phoneNumber
     *
     * @param PhoneNumber $phoneNumber
     * @return Employee
     */
    public function removePhoneNumber(PhoneNumber $phoneNumber)
    {
        $this->phoneNumbers->removeElement($phoneNumber);
        return $this;
    }

    /**
     * Get related event
     *
     * @return Event|null
     */
    public function getEvent(): ?Event
    {
        return $this->event;
    }

    /**
     * @param Event $event
     */
    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    /**
     * Set assignedUser
     *
     * @param User $assignedUser
     *
     * @return Employee
     */
    public function setAssignedUser(User $assignedUser = null)
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
     * @return bool
     */
    public function hasPredecessor(): bool
    {
        return $this->predecessor !== null;
    }
    
    /**
     * @return null|Employee
     */
    public function getPredecessor(): ?Employee
    {
        return $this->predecessor;
    }
    
    /**
     * @param null|Employee $predecessor
     * @return Employee
     */
    public function setPredecessor(?Employee $predecessor)
    {
        $this->predecessor = $predecessor;
        return $this;
    }
    
    /**
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->isConfirmed;
    }

    /**
     * @param bool $isConfirmed
     */
    public function setIsConfirmed(bool $isConfirmed = true): void
    {
        $this->isConfirmed = $isConfirmed;
    }

    /**
     * Create a new employee for transmitted event based on data of given employee
     *
     * @param Employee $employeePrevious Data template
     * @param Event $event                         Event this new employee should belong to
     * @param bool $copyPrivateFields              If set to true, also private acquisition data and user assignments
     *                                             are copied
     * @return Employee
     */
    public static function createFromTemplateForEvent(
        Employee $employeePrevious, Event $event, $copyPrivateFields = false
    )
    {
        /** @var Employee $employee */
        $employee = new self($event);
        $employee->setNameLast($employeePrevious->getNameLast());
        $employee->setNameFirst($employeePrevious->getNameFirst());
        $employee->setAddressCity($employeePrevious->getAddressCity());
        $employee->setAddressStreet($employeePrevious->getAddressStreet());
        $employee->setAddressZip($employeePrevious->getAddressZip());
        $employee->setAddressCountry($employeePrevious->getAddressCountry());
        $employee->setEmail($employeePrevious->getEmail());
        $employee->setSalutation($employeePrevious->getSalutation());
        $employee->setIsConfirmed(true);
        $employee->setPredecessor($employeePrevious);

        if ($copyPrivateFields) {
            $employee->setAssignedUser($employeePrevious->getAssignedUser());
        }

        /** @var PhoneNumber $numberPrevious */
        foreach ($employeePrevious->getPhoneNumbers() as $numberPrevious) {
            $number = new PhoneNumber();
            $number->setEmployee($employee);
            $number->setDescription($numberPrevious->getDescription());
            $number->setNumber($numberPrevious->getNumber());
            $employee->addPhoneNumber($number);
        }

        /** @var Attribute $attribute */
        foreach ($event->getAcquisitionAttributes(false, false, true, $copyPrivateFields, true) as $attribute) {
            try {
                $filloutPrevious = $employeePrevious->getAcquisitionAttributeFillout($attribute->getBid(), false);
            } catch (\OutOfBoundsException $e) {
                continue;
            }
            $fillout = new Fillout();
            $fillout->setEmployee($employee);
            $fillout->setAttribute($attribute);
            $fillout->setValue($filloutPrevious->getRawValue());
            $employee->addAcquisitionAttributeFillout($fillout);
        }

        return $employee;
    }
    
    /**
     * @inheritDoc
     */
    public function getChangeTrackingAttributeConverters(): array
    {
        return [];
    }
    
    /**
     * @inheritDoc
     */
    public function getComparableRepresentation()
    {
        return $this->getGid();
    }
    
    /**
     * @inheritDoc
     */
    public function getChangeTrackingStorableRepresentation()
    {
        return sprintf('%s @ %s [%d]', $this->fullname(), $this->getEvent()->getTitle(), $this->getGid());
    }
    
    /**
     * @inheritDoc
     */
    public static function getExcludedAttributes(): array
    {
        return [];
    }
}
