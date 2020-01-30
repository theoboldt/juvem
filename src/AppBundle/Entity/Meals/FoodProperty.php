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
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesCreatorInterface;
use AppBundle\Entity\Audit\ProvidesModifiedInterface;
use AppBundle\Entity\Audit\ProvidesModifierInterface;
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Participant;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="food_property")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 * @ORM\Entity(repositoryClass="FoodPropertyRepository")
 */
class FoodProperty implements SoftDeleteableInterface, ProvidesModifiedInterface, ProvidesCreatedInterface, ProvidesCreatorInterface, ProvidesModifierInterface
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
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @var string
     */
    protected $name;
    
    /**
     * If this food property is not permitted for a {@see Participant} or {@see Employee}, this is how a human would call it
     *
     * @ORM\Column(type="string", length=255, options={"default":""})
     * @var string
     */
    protected $exclusionTerm = '';
    
    /**
     * Extended descriptive form of exclusion term for use in forms
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $exclusionTermDescription = null;
    
    /**
     * Shortened info of exclusion term
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $exclusionTermShort = null;
    
    /**
     * FoodProperty constructor.
     *
     * @param string $name
     * @param string $exclusionTerm
     * @param string|null $exclusionTermDescription
     * @param string|null $exclusionTermShort
     */
    public function __construct(
        string $name,
        string $exclusionTerm = '',
        ?string $exclusionTermDescription = null,
        ?string $exclusionTermShort = null
    )
    {
        $this->name                     = $name;
        $this->exclusionTerm            = $exclusionTerm;
        $this->exclusionTermDescription = $exclusionTermDescription;
        $this->exclusionTermShort       = $exclusionTermShort;
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
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * @param string $name
     * @return FoodProperty
     */
    public function setName(string $name): FoodProperty
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getExclusionTerm(): string
    {
        return $this->exclusionTerm;
    }
    
    /**
     * @param string $exclusionTerm
     * @return FoodProperty
     */
    public function setExclusionTerm(string $exclusionTerm): FoodProperty
    {
        $this->exclusionTerm = $exclusionTerm;
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getExclusionTermDescription(): ?string
    {
        return $this->exclusionTermDescription;
    }
    
    /**
     * @param string|null $exclusionTermDescription
     * @return FoodProperty
     */
    public function setExclusionTermDescription(?string $exclusionTermDescription): FoodProperty
    {
        $this->exclusionTermDescription = $exclusionTermDescription;
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getExclusionTermShort(): ?string
    {
        return $this->exclusionTermShort;
    }
    
    /**
     * @param string|null $exclusionTermShort
     * @return FoodProperty
     */
    public function setExclusionTermShort(?string $exclusionTermShort): FoodProperty
    {
        $this->exclusionTermShort = $exclusionTermShort;
        return $this;
    }
}