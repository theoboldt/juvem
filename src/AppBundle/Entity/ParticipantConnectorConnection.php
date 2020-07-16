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


use AppBundle\Entity\Audit\CreatedTrait;
use AppBundle\Entity\Audit\CreatorTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Audit\ProvidesCreatorInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="participant_connector_connection")
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnly()
 */
class ParticipantConnectorConnection implements ProvidesCreatedInterface, ProvidesCreatorInterface
{
    use CreatedTrait, CreatorTrait;

    /**
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="ParticipantConnector", inversedBy="connections")
     * @ORM\JoinColumn(name="connector_id", referencedColumnName="id", onDelete="cascade")
     * @var ParticipantConnector
     */
    protected $connector;

    /**
     * ParticipantConnectorConnection constructor.
     *
     * @param ParticipantConnector $connector
     * @param User                 $creator
     */
    public function __construct(ParticipantConnector $connector, User $creator)
    {
        $this->connector = $connector;
        $this->setCreatedAtNow();
        $this->setCreatedBy($creator);
    }
    
    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return ParticipantConnector
     */
    public function getConnector(): ParticipantConnector
    {
        return $this->connector;
    }

}
