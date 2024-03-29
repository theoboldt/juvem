<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\AcquisitionAttribute;

use AppBundle\Entity\AcquisitionAttribute\Formula\ValidFormula as AssertValidFormula;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesModifiedInterface;
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingComparableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SpecifiesChangeTrackingStorableRepresentationInterface;
use AppBundle\Entity\ChangeTracking\SupportsChangeTrackingInterface;
use AppBundle\Entity\CustomField\BankAccountCustomFieldValue;
use AppBundle\Entity\CustomField\ChoiceCustomFieldValue;
use AppBundle\Entity\CustomField\CustomFieldValueInterface;
use AppBundle\Entity\CustomField\DateCustomFieldValue;
use AppBundle\Entity\CustomField\GroupCustomFieldValue;
use AppBundle\Entity\CustomField\NumberCustomFieldValue;
use AppBundle\Entity\CustomField\OptionProvidingCustomFieldValueInterface;
use AppBundle\Entity\CustomField\ParticipantDetectingCustomFieldValue;
use AppBundle\Entity\CustomField\TextualCustomFieldValue;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participation;
use AppBundle\Form\BankAccountType;
use AppBundle\Form\GroupType;
use AppBundle\Form\ParticipantDetectingType;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as FormChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType as FormDateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType as FormNumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType as FormTextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType as FormTextType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @AssertValidFormula()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AcquisitionAttribute\AcquisitionAttributeRepository")
 * @ORM\Table(name="acquisition_attribute")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 * @ORM\HasLifecycleCallbacks()
 */
class Attribute implements SoftDeleteableInterface, ProvidesModifiedInterface, ProvidesCreatedInterface, SupportsChangeTrackingInterface, SpecifiesChangeTrackingStorableRepresentationInterface, SpecifiesChangeTrackingComparableRepresentationInterface
{
    use CreatedModifiedTrait, SoftDeleteTrait;

    const DEFAULT_SORT = 99999;
    
    const LABEL_FIELD_TEXT        = 'Textfeld (Einzeilig)';
    const LABEL_FIELD_TEXTAREA    = 'Textfeld (Mehrzeilig)';
    const LABEL_FIELD_CHOICE      = 'Auswahl';
    const LABEL_FIELD_DATE        = 'Datum';
    const LABEL_FIELD_BANK        = 'Bankverbindung';
    const LABEL_FIELD_NUMBER      = 'Eingabefeld (Ganzzahl)';
    const LABEL_FIELD_GROUP       = 'Einteilungsfeld';
    const LABEL_FIELD_PARTICIPANT = 'Teilnehmerfeld';

    const FORMULA_VARIABLE_PREFIX = 'field';

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
     * @ORM\Column(type="text", name="form_description", length=65535)
     * @Assert\NotBlank()
     */
    protected $formDescription;

    /**
     * @ORM\Column(type="string", length=255, name="field_type")
     */
    protected $fieldType = FormTextType::class;

    /**
     * @ORM\Column(type="json_array", length=16777215, name="field_options", nullable=true)
     */
    protected $fieldOptions = [];

    /**
     * Sort value
     * 
     * @ORM\Column(type="integer", name="sort", options={"unsigned":true,"default":99999})
     */
    protected int $sort = self::DEFAULT_SORT;
    
    /**
     * @ORM\Column(name="use_at_participation", type="smallint", options={"unsigned":true,"default":0})
     *
     * @var boolean
     */
    protected $useAtParticipation = false;

    /**
     * @ORM\Column(name="use_at_participant", type="smallint", options={"unsigned":true,"default":0})
     *
     * @var boolean
     */
    protected $useAtParticipant = false;

    /**
     * @ORM\Column(name="use_at_employee", type="smallint", options={"unsigned":true,"default":0})
     *
     * @var boolean
     */
    protected $useAtEmployee = false;

    /**
     * @ORM\Column(name="is_required", type="smallint", options={"unsigned":true,"default":0})
     *
     * @var boolean
     */
    protected $isRequired = false;

    /**
     * Contains the events which use this attribute
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Event", mappedBy="acquisitionAttributes")
     */
    protected $events;

    /**
     * Stores if attribute is public (and included in form or not)
     *
     * @ORM\Column(type="boolean", name="is_public")
     */
    protected $isPublic = true;

    /**
     * Stores if attribute is archived and not suitable to be used for new events
     *
     * @ORM\Column(type="boolean", name="is_archived", options={"default":0})
     */
    protected $isArchived = false;

    /**
     * Stores if option is a built-in element
     *
     * @ORM\Column(type="boolean", name="is_system", options={"default":0})
     */
    protected $isSystem = false;
   
    /**
     * Contains the choice options the user can use
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption", cascade={"all"},
     *                                                                                            mappedBy="attribute")
     * @Assert\Valid()
     * @var Collection|array|AttributeChoiceOption[]
     */
    protected $choiceOptions;

    /**
     * @ORM\Column(name="is_price_formula_enabled", type="smallint", options={"unsigned":true,"default":0})
     *
     * @var boolean
     */
    protected $isPriceFormulaEnabled = false;

    /**
     * If set, contains a formula which has an effect on the price
     *
     * @ORM\Column(type="string", length=255, name="price_formula", nullable=true)
     */
    protected $priceFormula = null;

    /**
     * Specifies if custom field value accept comments/textual amendments
     * 
     * @ORM\Column(name="is_comment_enabled", type="smallint", options={"unsigned":true,"default":0})
     *
     * @var boolean
     */
    protected $isCommentEnabled = false;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->events                 = new ArrayCollection();
        $this->choiceOptions          = new ArrayCollection();
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
     * Get the name for the field, used in forms
     *
     * @deprecated
     * @return string
     */
    public function getName()
    {
        return 'acq_field_' . $this->bid;
    }

    /**
     * Get the name for the custom field
     *
     * @return string
     */
    public function getCustomFieldName(): string
    {
        return 'custom_field_' . $this->bid;
    }

    /**
     * Set managementTitle
     *
     * @param string $managementTitle
     *
     * @return Attribute
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
     * @return Attribute
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
     * @return Attribute
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
     * @return Attribute
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
        $this->createdAt = new DateTime();
    }

    /**
     * Set createdAt
     *
     * @param DateTime $createdAt
     *
     * @return self
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
     * @return Attribute
     */
    public function setFieldType($fieldType)
    {
        $this->fieldType = $fieldType;

        if ($fieldType == FormChoiceType::class) {
            $options             = $this->getFieldOptions();
            $options['expanded'] = true;
            $this->setFieldOptions($options);
        } else {
            $this->setFieldOptions([]);
        }

        return $this;
    }

    /**
     * Get fieldType
     *
     * @param bool $asLabel Set to true to return as label
     *
     * @return string
     */
    public function getFieldType($asLabel = false)
    {
        if ($asLabel) {
            switch ($this->fieldType) {
                case FormTextType::class:
                    return self::LABEL_FIELD_TEXT;
                case FormTextareaType::class;
                    return self::LABEL_FIELD_TEXTAREA;
                case FormChoiceType::class;
                    return self::LABEL_FIELD_CHOICE;
                case FormNumberType::class;
                    return self::LABEL_FIELD_NUMBER;
                case FormDateType::class:
                    return self::LABEL_FIELD_DATE;
                case BankAccountType::class;
                    return self::LABEL_FIELD_BANK;
                case GroupType::class;
                    return self::LABEL_FIELD_GROUP;
                case ParticipantDetectingType::class:
                    return self::LABEL_FIELD_PARTICIPANT;
            }
        }

        return $this->fieldType;
    }

    /**
     * Provide custom field value type used for this custom field
     * 
     * @todo remove after transition phase
     * @return string
     */
    public function getCustomFieldValueType(): string {
        switch ($this->fieldType) {
            case FormTextType::class:
            case FormTextareaType::class;
                return TextualCustomFieldValue::TYPE;
            case FormChoiceType::class;
                return ChoiceCustomFieldValue::TYPE;
            case FormNumberType::class;
                return NumberCustomFieldValue::TYPE;
            case FormDateType::class:
                return DateCustomFieldValue::TYPE;
            case BankAccountType::class;
                return BankAccountCustomFieldValue::TYPE;
            case GroupType::class;
                return GroupCustomFieldValue::TYPE;
            case ParticipantDetectingType::class:
                return ParticipantDetectingCustomFieldValue::TYPE;
            default:
                throw new \RuntimeException('Unknown type ' . $this->fieldType . ' occurred');
        }
    }

    /**
     * Convert a transmitted custom field value to a textual representation
     *
     * @param CustomFieldValueInterface $customFieldValue
     * @return string
     */
    public function getTextualValue(CustomFieldValueInterface $customFieldValue): string
    {
        if ($customFieldValue instanceof OptionProvidingCustomFieldValueInterface) {
            $textualOptions = [];
            foreach ($customFieldValue->getSelectedChoices() as $choiceId) {
                $choice = $this->getChoiceOption($choiceId);
                if ($choice) {
                    $textualOptions[] = $choice->getManagementTitle(true);
                } else {
                    $textualOptions[] = $choiceId;
                }
            }
            if (count($textualOptions)) {
                sort($textualOptions);
                return implode(', ', $textualOptions);
            } else {
                return '';
            }
        } else {
            return $customFieldValue->getTextualValue();
        }
    }
    
    /**
     * Set fieldOptions
     *
     * @param array $fieldOptions
     *
     * @return Attribute
     */
    public function setFieldOptions($fieldOptions)
    {
        if ($fieldOptions === null) {
            $fieldOptions = [];
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
        $options = $this->fieldOptions;
        if (!$options) {
            $options = [];
        }
        $options['label']    = $this->getFormTitle();
        $options['required'] = $this->isRequired();
        $options['mapped']   = true;

        if ($this->isChoiceType()) {
            $options['placeholder'] = 'keine Option gewählt';
            $options['choices']     = [];
            /** @var AttributeChoiceOption $choice */
            foreach ($this->choiceOptions->getIterator() as $choice) {
                if ($choice->getDeletedAt() === null) {
                    $options['choices'][$choice->getFormTitle()] = $choice->getId();
                }
            }
        } elseif ($this->getFieldType() === FormDateType::class) {
            $options['years']  = range(Date('Y') - 100, Date('Y') + 1);
            $options['format'] = 'dd.MM.yyyy';
        }

        return $options;
    }

    /**
     * Determine if this attribute provides choice options
     *
     * @return bool
     */
    public function isChoiceType(): bool
    {
        $type = $this->getFieldType();
        return $type === FormChoiceType::class || $type === GroupType::class;
    }

    /**
     * Set field choices if this is a choice attribute
     *
     * @param boolean $multiple
     *
     * @return Attribute
     */
    public function setIsMultipleChoiceType(bool $multiple)
    {
        if ($this->isChoiceType()) {
            $options             = $this->getFieldOptions();
            $options['multiple'] = (bool)$multiple;
            $this->setFieldOptions($options);
        }

        return $this;
    }

    /**
     * Returns true if this is a choice field and provides multiple options
     *
     * @return bool
     */
    public function isMultipleChoiceType(): bool
    {
        $options = $this->getFieldOptions();

        if ($this->isChoiceType() && isset($options['multiple'])) {
            return $options['multiple'];
        }

        return false;
    }

    /**
     * Get field choices if this is a choice attribute
     *
     * @param bool $asArray Set to true to return as array
     *
     * @return array
     */
    public function getFieldTypeChoiceOptions($asArray = false)
    {
        $options = $this->getFieldOptions();

        if ($this->getFieldType() == FormChoiceType::class && isset($options['choices'])) {
            if ($asArray) {
                return $options['choices'];
            } else {
                return implode(';', array_keys($options['choices']));
            }
        }

        if ($asArray) {
            return [];
        }

        return '';
    }

    /**
     * Set useAtParticipation
     *
     * @param boolean $useAtParticipation
     *
     * @return Attribute
     */
    public function setUseAtParticipation($useAtParticipation = true)
    {
        $this->useAtParticipation = (bool)$useAtParticipation;

        return $this;
    }

    /**
     * Get useAtParticipation
     *
     * @return boolean
     */
    public function getUseAtParticipation()
    {
        return (bool)$this->useAtParticipation;
    }

    /**
     * Set useAtParticipant
     *
     * @param boolean $useAtParticipant
     *
     * @return Attribute
     */
    public function setUseAtParticipant($useAtParticipant = true)
    {
        $this->useAtParticipant = (bool)$useAtParticipant;

        return $this;
    }

    /**
     * Get useAtParticipant
     *
     * @return boolean
     */
    public function getUseAtParticipant()
    {
        return (bool)$this->useAtParticipant;
    }


    /**
     * Determine if this field is used for @see Employee
     *
     * @return bool
     */
    public function getUseAtEmployee(): bool
    {
        return $this->useAtEmployee;
    }

    /**
     * Determine if this field is used at @see Participation or @see Participant
     *
     * @return bool
     */
    public function isUseForParticipationsOrParticipants(): bool
    {
        return $this->getUseAtParticipation() || $this->getUseAtParticipant();
    }

    /**
     * @param bool $useAtEmployee
     * @return Attribute
     */
    public function setUseAtEmployee(bool $useAtEmployee): Attribute
    {
        $this->useAtEmployee = $useAtEmployee;
        return $this;
    }

    /**
     * Set isRequired
     *
     * @param boolean $isRequired
     *
     * @return Attribute
     */
    public function setIsRequired($isRequired = true)
    {
        $this->isRequired = (bool)$isRequired;

        return $this;
    }

    /**
     * Get isRequired
     *
     * @return boolean
     */
    public function isRequired()
    {
        return (bool)$this->isRequired;
    }

    /**
     * Get if attribute is public (and included in form or not)
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return (bool)$this->isPublic;
    }

    /**
     * Set if attribute is public (and included in form or not)
     *
     * @param bool $isPublic
     * @return Attribute
     */
    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    /**
     * Add event
     *
     * @param Event $event
     *
     * @return Attribute
     */
    public function addEvent(Event $event)
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * Remove event
     *
     * @param Event $event
     */
    public function removeEvent(Event $event)
    {
        $this->events->removeElement($event);
    }

    /**
     * Get events
     *
     * @return Collection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Add choiceOption
     *
     * @param AttributeChoiceOption $choiceOption
     *
     * @return Attribute
     */
    public function addChoiceOption(AttributeChoiceOption $choiceOption)
    {
        if (!$this->choiceOptions->contains($choiceOption)) {
            $this->choiceOptions->add($choiceOption);
        }
        $choiceOption->setAttribute($this);

        return $this;
    }

    /**
     * Remove choiceOption
     *
     * @param AttributeChoiceOption $choiceOption
     */
    public function removeChoiceOption(AttributeChoiceOption $choiceOption)
    {
        if (!$choiceOption->getDeletedAt()) {
            $choiceOption->setDeletedAt(new DateTime());
        }
    }

    /**
     * Get choiceOptions
     *
     * @return Collection
     */
    public function getChoiceOptions()
    {
        return $this->choiceOptions;
    }

    /**
     * Get single @param int $id Id of option
     *
     * @return AttributeChoiceOption|null
     * @see AttributeChoiceOption
     *
     */
    public function getChoiceOption(int $id): ?AttributeChoiceOption
    {
        foreach ($this->choiceOptions as $choiceOption) {
            if ($choiceOption->getId() === $id) {
                return $choiceOption;
            }
        }
        return null;
    }

    /**
     * Determine if price formula handling is enabled or not
     *
     * @return bool Result
     */
    public function isPriceFormulaEnabled(): bool
    {
        return $this->isPriceFormulaEnabled;
    }

    /**
     * Set if  price formula handling is enabled or not
     *
     * @param bool $isPriceFormulaEnabled New Option
     * @return self Self
     */
    public function setIsPriceFormulaEnabled(bool $isPriceFormulaEnabled)
    {
        $this->isPriceFormulaEnabled = $isPriceFormulaEnabled;
        return $this;
    }

    /**
     * @return int
     */
    public function getSort(): int
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     */
    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }
    
    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->isArchived;
    }
    
    /**
     * @param bool $isArchived
     * @return Attribute
     */
    public function setIsArchived(bool $isArchived): Attribute
    {
        $this->isArchived = $isArchived;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    /**
     * @param bool $isSystem
     */
    public function setIsSystem(bool $isSystem): void
    {
        $this->isSystem = $isSystem;
    }
    
    /**
     * Get price formula if set
     *
     * @return string|null
     */
    public function getPriceFormula(): ?string
    {
        return $this->priceFormula;
    }

    /**
     * Set new price formula
     *
     * @param string|null $priceFormula Textual formula
     * @return $this
     */
    public function setPriceFormula(string $priceFormula = null)
    {
        $this->priceFormula = $priceFormula;
        return $this;
    }

    /**
     * Get formula variable name
     *
     * @return string
     */
    public function getFormulaVariable() {
        return self::FORMULA_VARIABLE_PREFIX.$this->getBid();
    }

    /**
     * Determine if custom field value accept comments/textual amendments for this field
     * 
     * @return bool
     */
    public function isCommentEnabled(): bool
    {
        return $this->isCommentEnabled;
    }

    /**
     * Configure if custom field value accept comments/textual amendments for this field
     * 
     * @param bool $isCommentEnabled
     * @return Attribute
     */
    public function setIsCommentEnabled(bool $isCommentEnabled): Attribute
    {
        $this->isCommentEnabled = $isCommentEnabled;
        return $this;
    }
    
    /**
     * @inheritDoc
     */
    public function getChangeTrackingStorableRepresentation()
    {
        return sprintf('%s [%d]', $this->getManagementTitle(), $this->getBid());
    }
    
    /**
     * @inheritDoc
     */
    public function getComparableRepresentation()
    {
        return $this->getBid();
    }
    
    /**
     * @inheritDoc
     */
    public function getId(): ?int
    {
        return $this->getBid();
    }
    
    /**
     * @inheritDoc
     */
    public static function getExcludedAttributes(): array
    {
        return ['fieldOptions'];
    }
}
