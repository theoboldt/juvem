<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\SearchParticipant;

class ParticipantSearch
{

    const INCLUDE_EVENT_ACTIVE = 'event_active';

    const INCLUDE_EVENT_ALL = 'event_all';

    /**
     * @var ?string
     */
    private ?string $participationEmail = null;

    /**
     * @var ?string
     */
    private ?string $participationFirstName = null;

    /**
     * @var ?string
     */
    private ?string $participationLastName = null;

    /**
     * @var ?string
     */
    private ?string $participantFirstName = null;

    /**
     * @var ?string
     */
    private ?string $participantLastName = null;

    /**
     * @var string
     */
    private string $eventFilter = self::INCLUDE_EVENT_ALL;

    /**
     * @return ?string
     */
    public function getParticipationEmail(): ?string
    {
        return $this->participationEmail;
    }

    /**
     * @param ?string $participationEmail
     */
    public function setParticipationEmail(?string $participationEmail): void
    {
        $this->participationEmail = $participationEmail;
    }

    /**
     * @return ?string
     */
    public function getParticipationFirstName(): ?string
    {
        return $this->participationFirstName;
    }

    /**
     * @param ?string $participationFirstName
     */
    public function setParticipationFirstName(?string $participationFirstName): void
    {
        $this->participationFirstName = $participationFirstName;
    }

    /**
     * @return ?string
     */
    public function getParticipationLastName(): ?string
    {
        return $this->participationLastName;
    }

    /**
     * @param ?string $participationLastName
     */
    public function setParticipationLastName(?string $participationLastName): void
    {
        $this->participationLastName = $participationLastName;
    }

    /**
     * @return ?string
     */
    public function getParticipantFirstName(): ?string
    {
        return $this->participantFirstName;
    }

    /**
     * @param ?string $participantFirstName
     */
    public function setParticipantFirstName(?string $participantFirstName): void
    {
        $this->participantFirstName = $participantFirstName;
    }

    /**
     * @return ?string
     */
    public function getParticipantLastName(): ?string
    {
        return $this->participantLastName;
    }

    /**
     * @param ?string $participantLastName
     */
    public function setParticipantLastName(?string $participantLastName): void
    {
        $this->participantLastName = $participantLastName;
    }

    /**
     * @return string
     */
    public function getEventFilter(): string
    {
        return $this->eventFilter;
    }

    /**
     * @param string $eventFilter
     */
    public function setEventFilter(string $eventFilter): void
    {
        if (!in_array($eventFilter, [self::INCLUDE_EVENT_ACTIVE, self::INCLUDE_EVENT_ALL])) {
            throw new \InvalidArgumentException('Unknown event filter ' . $eventFilter . ' transmitted');
        }
        $this->eventFilter = $eventFilter;
    }

}
