<?php
namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="acquisition_attribute")
 * @Gedmo\SoftDeleteable(fieldName="deleted_at", timeAware=false)
 * @ORM\HasLifecycleCallbacks()
 */
class AcquisitionAttribute
{

    const LABEL_FIELD_TEXT     = 'Textfeld (Einzeilig)';
    const LABEL_FIELD_TEXTAREA = 'Textfeld (Mehrzeilig)';
    const LABEL_FIELD_CHOICE   = 'Auswahl';

    /**
     * @ORM\Column(type="integer", name="bid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $bid;

    /**
     * @ORM\Column(type="string", length=255, name="management_title")
     * @Assert\NotBlank()
     */
    protected $managementTitle;

    /**
     * @ORM\Column(type="string", length=255, name="management_description")
     * @Assert\NotBlank()
     */
    protected $managementDescription;

    /**
     * @ORM\Column(type="string", length=255, name="form_title")
     * @Assert\NotBlank()
     */
    protected $formTitle;

    /**
     * @ORM\Column(type="string", length=255, name="form_description")
     * @Assert\NotBlank()
     */
    protected $formDescription;

    /**
     * @ORM\Column(type="string", length=255, name="field_type")
     */
    protected $fieldType = TextType::class;

    /**
     * @ORM\Column(type="json_array", length=255, name="field_options")
     */
    protected $fieldOptions = array();

    /**
     * @ORM\Column(name="use_at_participation", type="boolean", options={"unsigned":true,"default":0})
     *
     * @var boolean
     */
    protected $useAtParticipation = false;

    /**
     * @ORM\Column(name="use_at_participant", type="boolean", options={"unsigned":true,"default":0})
     *
     * @var boolean
     */
    protected $useAtParticipant = false;

    /**
     * Contains the events which use this attribute
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Event", mappedBy="acquisitionAttributes")
     */
    protected $events;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", name="modified_at")
     */
    protected $modifiedAt;

    /**
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     */
    protected $deletedAt = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    /**
     * Get bid
     *
     * @return integer
     */
    public function getBid()
    {
        return $this->bid;
    }

    /**
     * Set managementTitle
     *
     * @param string $managementTitle
     *
     * @return AcquisitionAttribute
     */
    public function setManagementTitle($managementTitle)
    {
        $this->managementTitle = $managementTitle;

        return $this;
    }

    /**
     * Get managementTitle
     *
     * @return string
     */
    public function getManagementTitle()
    {
        return $this->managementTitle;
    }

    /**
     * Set managementDescription
     *
     * @param string $managementDescription
     *
     * @return AcquisitionAttribute
     */
    public function setManagementDescription($managementDescription)
    {
        $this->managementDescription = $managementDescription;

        return $this;
    }

    /**
     * Get managementDescription
     *
     * @return string
     */
    public function getManagementDescription()
    {
        return $this->managementDescription;
    }

    /**
     * Set formTitle
     *
     * @param string $formTitle
     *
     * @return AcquisitionAttribute
     */
    public function setFormTitle($formTitle)
    {
        $this->formTitle = $formTitle;

        return $this;
    }

    /**
     * Get formTitle
     *
     * @return string
     */
    public function getFormTitle()
    {
        return $this->formTitle;
    }

    /**
     * Set formDescription
     *
     * @param string $formDescription
     *
     * @return AcquisitionAttribute
     */
    public function setFormDescription($formDescription)
    {
        $this->formDescription = $formDescription;

        return $this;
    }

    /**
     * Get formDescription
     *
     * @return string
     */
    public function getFormDescription()
    {
        return $this->formDescription;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtNow()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Event
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Set fieldType
     *
     * @param string $fieldType
     *
     * @return AcquisitionAttribute
     */
    public function setFieldType($fieldType)
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    /**
     * Get fieldType
     *
     * @return string
     */
    public function getFieldType($asLabel = false)
    {
        if ($asLabel) {
            switch ($this->fieldType) {
                case TextType::class:
                    return self::LABEL_FIELD_TEXT;
                case TextareaType::class;
                    return self::LABEL_FIELD_TEXTAREA;
                case ChoiceType::class;
                    return self::LABEL_FIELD_CHOICE;
            }
        }

        return $this->fieldType;
    }

    /**
     * Set fieldOptions
     *
     * @param array $fieldOptions
     *
     * @return AcquisitionAttribute
     */
    public function setFieldOptions($fieldOptions)
    {
        if ($fieldOptions === null) {
            $fieldOptions = array();
        }
        $this->fieldOptions = $fieldOptions;

        return $this;
    }

    /**
     * Get fieldOptions
     *
     * @return array
     */
    public function getFieldOptions()
    {
        return $this->fieldOptions;
    }

    /**
     * Set useAtParticipation
     *
     * @param boolean $useAtParticipation
     *
     * @return AcquisitionAttribute
     */
    public function setUseAtParticipation($useAtParticipation = true)
    {
        $this->useAtParticipation = $useAtParticipation;

        return $this;
    }

    /**
     * Get useAtParticipation
     *
     * @return boolean
     */
    public function getUseAtParticipation()
    {
        return $this->useAtParticipation;
    }

    /**
     * Set useAtParticipant
     *
     * @param boolean $useAtParticipant
     *
     * @return AcquisitionAttribute
     */
    public function setUseAtParticipant($useAtParticipant = true)
    {
        $this->useAtParticipant = $useAtParticipant;

        return $this;
    }

    /**
     * Get useAtParticipant
     *
     * @return boolean
     */
    public function getUseAtParticipant()
    {
        return $this->useAtParticipant;
    }

    /**
     * Add event
     *
     * @param \AppBundle\Entity\Event $event
     *
     * @return AcquisitionAttribute
     */
    public function addEvent(\AppBundle\Entity\Event $event)
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * Remove event
     *
     * @param \AppBundle\Entity\Event $event
     */
    public function removeEvent(\AppBundle\Entity\Event $event)
    {
        $this->events->removeElement($event);
    }

    /**
     * Get events
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function setModifiedAtNow()
    {
        $this->modifiedAt = new \DateTime();
    }

    /**
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     *
     * @return Event
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Event
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

}
