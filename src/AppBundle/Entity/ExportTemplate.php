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

use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\CreatorModifierTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesCreatorInterface;
use AppBundle\Entity\Audit\ProvidesModifiedInterface;
use AppBundle\Entity\Audit\ProvidesModifierInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="export_template")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ExportTemplateRepository")
 */
class ExportTemplate implements ProvidesModifiedInterface, ProvidesCreatedInterface, ProvidesCreatorInterface, ProvidesModifierInterface
{
    use CreatedModifiedTrait, CreatorModifierTrait;
    
    /**
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    protected $title;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description = null;
    
    /**
     * @ORM\Column(type="json_array", length=16777215, name="configuration", nullable=true)
     */
    protected $configuration = [];
    
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event")
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="SET NULL")
     *
     * @var Event|null
     */
    protected $event;
    
    /**
     * ExportTemplate constructor.
     *
     * @param Event|null $event
     * @param string|null $title
     * @param string|null $description
     * @param array $configuration
     */
    public function __construct(
        ?Event $event = null, ?string $title = null, ?string $description = null, array $configuration = []
    )
    {
        $this->event         = $event;
        $this->title         = $title;
        $this->description   = $description;
        $this->configuration = $configuration;
        $this->setCreatedAtNow();
    }
    
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @return Event|null
     */
    public function getEvent(): ?Event
    {
        return $this->event;
    }
    
    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
    
    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
    
    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    /**
     * @param string|null $description
     */
    public function setDescription(?string $description = null): void
    {
        $this->description = $description;
    }
    
    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }
    
    /**
     * @param mixed $configuration
     */
    public function setConfiguration(array $configuration = []): void
    {
        $this->configuration = $configuration;
    }
    
    
}