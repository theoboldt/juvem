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
use AppBundle\Entity\Audit\SoftDeleteableInterface;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="participant_connector", indexes={@ORM\Index(name="deleted_at_idx", columns={"deleted_at"})})
 * @ORM\HasLifecycleCallbacks()
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnly()
 */
class ParticipantConnector implements ProvidesCreatedInterface, ProvidesCreatorInterface, ProvidesModifiedInterface, ProvidesModifierInterface, SoftDeleteableInterface
{
    use CreatedModifiedTrait, CreatorModifierTrait, SoftDeleteTrait;

    /**
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Participant", inversedBy="connectors")
     * @ORM\JoinColumn(name="aid", referencedColumnName="aid", onDelete="cascade")
     * @var Participant
     */
    protected $participant;

    /**
     * @ORM\Column(type="string", length=32)
     * @Assert\Length(max=32)
     * @Assert\NotBlank()
     * @var string
     */
    protected $token;

    /**
     * @ORM\Column(type="string", length=255, name="description")
     * @Assert\Length(max=255)
     */
    protected $description = '';

    /**
     * Connectors
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ParticipantConnectorConnection",
     *     cascade={"all"}, mappedBy="connector")
     */
    protected $connections;
    
    /**
     * ParticipantConnector constructor.
     *
     * @param Participant $participant
     * @param string $description
     * @param string $token
     */
    public function __construct(Participant $participant, string $description = '', ?string $token = null)
    {
        $this->setCreatedAtNow();
        $this->participant = $participant;
        $this->description = $description;
        $this->connections = new ArrayCollection();
        if (!$token) {
            $token = self::createToken();
        }
        $this->token = $token;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Participant
     */
    public function getParticipant(): Participant
    {
        return $this->participant;
    }
    
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * @param string $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description ?: '';
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return array|ParticipantConnectorConnection[]
     */
    public function getConnections(): array
    {
        return $this->connections->toArray();
    }

    public function createConnection(?User $user): ParticipantConnectorConnection
    {
        $connection = new ParticipantConnectorConnection($this, $user);
        $this->connections->add($connection);
        return $connection;
    }

    /**
     * @param ParticipantConnectorConnection $connection
     */
    public function removeConnection(ParticipantConnectorConnection $connection): void
    {
        $this->connections->removeElement($connection);
    }

    /**
     * Create secure random token of 40 characters length
     *
     * @return string
     */
    public static function createToken(): string
    {
        $token = '';
        while (strlen($token) < 32) {
            $group = random_int(0, 2);
            switch ($group) {
                case 0:
                    $token .= chr(random_int(48, 57));
                    break;
                case 1:
                    $token .= chr(random_int(65, 90));
                    break;
                case 2:
                    $token .= chr(random_int(97, 122));
                    break;
            }
        }

        return $token;
    }


}
