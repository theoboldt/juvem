<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\Meals;

use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\CreatorModifierTrait;
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="quantity_unit")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 * @ORM\Entity(repositoryClass="QuantityUnitRepository")
 */
class QuantityUnit implements SoftDeleteableInterface
{
    use CreatedModifiedTrait, SoftDeleteTrait, CreatorModifierTrait;
    
    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int|null
     */
    protected $id;
    
    /**
     * Title
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     * @var string
     */
    protected $name;
    
    /**
     * Shortened name
     *
     * @ORM\Column(type="string", length=32)
     * @Assert\Length(max=32)
     * @var string
     */
    protected $short;
    
    /**
     * List of uses of this quantity
     *
     * @ORM\OneToMany(targetEntity="Viand", cascade={"all"}, mappedBy="defaultUnit")
     */
    protected $usedAsDefault;
    
    /**
     * List of uses of this quantity
     *
     * @ORM\OneToMany(targetEntity="RecipeIngredient", cascade={"all"}, mappedBy="unit")
     */
    protected $usesInRecipes;
    
    /**
     * QuantityUnit constructor.
     *
     * @param null|string $name
     * @param null|string $short
     */
    public function __construct(?string $name = null, ?string $short = null)
    {
        $this->name          = $name;
        $this->short         = $short;
        $this->usedAsDefault = new ArrayCollection();
        $this->usesInRecipes = new ArrayCollection();
        $this->setCreatedAtNow();
    }
    
    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }
    
    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }
    
    public function getNameAndShort(): string
    {
        return $this->name . ' [' . $this->getShort() . ']';
    }
    
    /**
     * @param string $name
     * @return QuantityUnit
     */
    public function setName(string $name): QuantityUnit
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getShort(): ?string
    {
        return $this->short;
    }
    
    /**
     * @param string $short
     * @return QuantityUnit
     */
    public function setShort(string $short): QuantityUnit
    {
        $this->short = $short;
        return $this;
    }
    
    
}