<?php
/**
 * AttributeChoiceOption
 *
 * @author       Erik Theoboldt <theoboldt@teqneers.de>
 * @package    DDS
 * @subpackage AppBundle\Entity\AcquisitionAttribute
 * @copyright  Copyright (C) 2003-2017 TEQneers GmbH & Co. KG. All rights reserved.
 */

/**
 * AttributeChoiceOption
 *
 * @package    DDS
 * @subpackage AppBundle\Entity\AcquisitionAttribute
 */

namespace AppBundle\Entity\AcquisitionAttribute;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="acquisition_attribute_choice_option")
 * @Gedmo\SoftDeleteable(fieldName="deleted_at", timeAware=false)
 */
class AttributeChoiceOption {
    
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
     * @ORM\Column(type="string", length=255, name="management_title")
     * @Assert\NotBlank()
     */
    protected $managementTitle;
    
    /**
     * @ORM\Column(type="string", length=255, name="short_title", nullable=true)
     */
    protected $shortTitle = null;
    
    /**
     * @ORM\ManyToOne(targetEntity="Attribute", inversedBy="choiceOptions")
     * @ORM\JoinColumn(name="bid", referencedColumnName="bid", onDelete="cascade")
     */
    protected $attribute;
    
    /**
     * @ORM\Column(name="hide_in_form", type="smallint", options={"unsigned":true,"default":0})
     *
     * @var boolean
     */
    protected $hideInForm = false;
    
    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }
    
    /**
     * @return string
     */
    public function getFormTitle() {
        return $this->formTitle;
    }
    
    /**
     * @return string
     */
    public function getManagementTitle() {
        return $this->managementTitle;
    }
    
    /**
     * @return string|null
     */
    public function getShortTitle() {
        return $this->shortTitle;
    }
    
    /**
     * @return Attribute
     */
    public function getAttribute() {
        return $this->attribute;
    }
    
    /**
     * @return bool
     */
    public function isHideInForm(): bool {
        return $this->hideInForm;
    }
    
}