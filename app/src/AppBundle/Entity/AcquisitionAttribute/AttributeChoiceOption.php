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

use AppBundle\Entity\AcquisitionAttribute\Formula\OnlyNumericManagementDescriptionsUsed as AssertOnlyNumericManagementDescriptionsUsed;
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @AssertOnlyNumericManagementDescriptionsUsed()
 * @ORM\Entity
 * @ORM\Table(name="acquisition_attribute_choice_option")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 */
class AttributeChoiceOption implements SoftDeleteableInterface
{
    use SoftDeleteTrait;

    const PRESENTATION_FORM_TITLE = 'form_title';
    const PRESENTATION_MANAGEMENT_TITLE = 'management_title';
    const PRESENTATION_SHORT_TITLE = 'short';

    /**
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, name="form_title")
     * @Assert\NotBlank()
     */
    protected $formTitle;

    /**
     * @ORM\Column(type="string", length=255, name="management_title", nullable=true)
     */
    protected $managementTitle = null;

    /**
     * @ORM\Column(type="string", length=255, name="short_title", nullable=true)
     */
    protected $shortTitle = null;

    /**
     * @ORM\Column(type="text", name="form_description", length=65535)
     */
    protected $formDescription = '';

    /**
     * Stores if option is archived and not suitable to be used for new events
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
     * @ORM\ManyToOne(targetEntity="Attribute", inversedBy="choiceOptions")
     * @ORM\JoinColumn(name="bid", referencedColumnName="bid", onDelete="cascade")
     */
    protected $attribute;

    /**
     * @return mixed
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return AttributeChoiceOption
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFormTitle()
    {
        return $this->formTitle;
    }

    /**
     * @param mixed $formTitle
     * @return AttributeChoiceOption
     */
    public function setFormTitle($formTitle)
    {
        $this->formTitle = $formTitle;
        return $this;
    }

    /**
     * Determine if management title is set
     *
     * @return bool
     */
    public function hasManagementTitle()
    {
        return $this->managementTitle !== null;
    }

    /**
     * Get internal title
     *
     * @param bool $fallback If set to true and no internal title is set, form title is used
     * @return string|null
     */
    public function getManagementTitle($fallback = false)
    {
        if ($this->managementTitle === null && $fallback) {
            return $this->formTitle;
        }
        return $this->managementTitle;
    }

    /**
     * @param mixed $managementTitle
     * @return AttributeChoiceOption
     */
    public function setManagementTitle($managementTitle)
    {
        $this->managementTitle = $managementTitle;
        return $this;
    }

    /**
     * Get shortened title
     *
     * @param bool $fallback If set to true, short title is automatically generated
     * @return string|null
     */
    public function getShortTitle($fallback = false)
    {
        if ($this->shortTitle === null && $fallback) {
            $words = explode(' ', $this->getManagementTitle(true));
            $title = '';
            foreach ($words as $w) {
                $title .= mb_strtoupper($w[0]);
            }
            return $title;
        }

        return $this->shortTitle;
    }

    /**
     * @param string $shortTitle
     * @return AttributeChoiceOption
     */
    public function setShortTitle($shortTitle)
    {
        $this->shortTitle = $shortTitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormDescription(): string
    {
        return $this->formDescription;
    }

    /**
     * @param string|null $formDescription
     */
    public function setFormDescription(?string $formDescription): void
    {
        if ($formDescription === null) {
            $formDescription = '';
        }
        $this->formDescription = trim($formDescription);
    }

    /**
     * Determine if form description is available
     * 
     * @return bool
     */
    public function hasFormDescription(): bool
    {
        return !empty($this->formDescription);
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
     */
    public function setIsArchived(bool $isArchived): void
    {
        $this->isArchived = $isArchived;
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
     * Get related @see Attribute
     *
     * @return Attribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Set related attribute
     *
     * @param Attribute $attribute
     * @return AttributeChoiceOption
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
        if (!$attribute->getChoiceOptions()->contains($this)) {
            $attribute->addChoiceOption($this);
        }
        return $this;
    }

    /**
     * Transform option to text
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getFormTitle();
    }
}
