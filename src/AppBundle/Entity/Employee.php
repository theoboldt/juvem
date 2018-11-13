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


use AppBundle\Entity\AcquisitionAttribute\FilloutTrait;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="employee")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EmployeeRepository")
 * @Gedmo\SoftDeleteable(fieldName="deleted_at", timeAware=false)
 */
class Employee implements EventRelatedEntity
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
     * Contains the phone numbers assigned to this participation
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
     * Contains the participants assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AcquisitionAttribute\Fillout",
     *     cascade={"all"}, mappedBy="employee")
     */
    protected $acquisitionAttributeFillouts;
    
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="assignedParticipations")
     * @ORM\JoinColumn(name="uid", referencedColumnName="uid", onDelete="SET NULL")
     */
    protected $assignedUser;
    
    /**
     * Employee constructor.
     */
    public function __construct()
    {
        $this->acquisitionAttributeFillouts = new ArrayCollection();
        
        $this->phoneNumbers = new ArrayCollection();
        $this->comments     = new ArrayCollection();
        $this->modifiedAt   = new \DateTime();
        $this->createdAt    = new \DateTime();
    }
    
    /**
     * Get employee id
     *
     * @return int
     */
    public function getGid(): int
    {
        return $this->gid;
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
     * Get phone Numbers
     *
     * @return array
     */
    public function getPhoneNumbers(): array
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
        $this->phoneNumbers[] = $phoneNumber;
        $phoneNumber->setParticipation($this);
        
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
    public function getEvent()
    {
        return $this->event;
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
    
}
