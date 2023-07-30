<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\CustomField;

class ParticipantDetectingCustomFieldValue implements CustomFieldValueInterface
{
    const TYPE = 'participant_detecting';

    const KEY_VALUE = 'value';

    const KEY_SYSTEM_SELECTION = 'system_selection';

    const KEY_SELECTED_AID = 'participant_selected';

    const KEY_PARTICIPANT_DETECTING_LAST_NAME = 'participantDetectingLastName';

    const KEY_PARTICIPANT_DETECTING_FIRST_NAME = 'participantDetectingFirstName';

    const KEY_PARTICIPANT_SELECTED_LAST_NAME = 'participant_selected_last_name';

    const KEY_PARTICIPANT_SELECTED_FIRST_NAME = 'participant_selected_first_name';

    const KEY_PROPOSED_PARTICIPANTS = 'proposed_aids';

    /**
     * True if selection was done automatically by system because of exact match
     *
     * @var bool
     */
    private bool $systemSelection = false;

    /**
     * Textual original first name value of participant
     *
     * @var string|null
     */
    private ?string $relatedFirstName;

    /**
     * Textual original last name value of participant
     *
     * @var string|null
     */
    private ?string $relatedLastName;

    /**
     * Aid of {@see Participant} if one was selected for this user
     *
     * @var int|null
     */
    private ?int $participantAid = null;

    /**
     * First name of selected {@see Participant}
     *
     * @var null|string
     */
    private ?string $participantFirstName = null;

    /**
     * Last name of selected {@see Participant}
     *
     * @var null|string
     */
    private ?string $participantLastName = null;

    /**
     * List of {@see Participant} ids which are proposed to be related to this user
     *
     * List of {@see Participant} ids which are proposed to be related to this user. Use null if not initialized
     *
     * @var int[]|null
     */
    private ?array $proposedParticipants = null;

    public static function createFromArray(array $data): CustomFieldValueInterface
    {
         return new self(
            $data[self::KEY_VALUE][self::KEY_SYSTEM_SELECTION] ?? false,
            $data[self::KEY_VALUE][self::KEY_PARTICIPANT_DETECTING_FIRST_NAME] ?? '',
            $data[self::KEY_VALUE][self::KEY_PARTICIPANT_DETECTING_LAST_NAME] ?? '',
            $data[self::KEY_VALUE][self::KEY_SELECTED_AID] ?? null,
            $data[self::KEY_VALUE][self::KEY_PARTICIPANT_SELECTED_FIRST_NAME] ?? null,
            $data[self::KEY_VALUE][self::KEY_PARTICIPANT_SELECTED_LAST_NAME] ?? null,
            $data[self::KEY_VALUE][self::KEY_PROPOSED_PARTICIPANTS] ?? null,
        );
    }

    /**
     * Construct
     *
     * @param bool        $systemSelection
     * @param string|null $relatedFirstName
     * @param string|null $relatedLastName
     * @param int|null    $participantAid
     * @param string|null $participantFirstName
     * @param string|null $participantLastName
     * @param int[]|null  $proposedParticipants
     */
    public function __construct(
        bool    $systemSelection = false,
        ?string $relatedFirstName = null,
        ?string $relatedLastName = null,
        ?int    $participantAid = null,
        ?string $participantFirstName = null,
        ?string $participantLastName = null,
        ?array  $proposedParticipants = null
    ) {
        $this->systemSelection      = $systemSelection;
        $this->relatedFirstName     = $relatedFirstName;
        $this->relatedLastName      = $relatedLastName;
        $this->participantAid       = $participantAid;
        $this->participantFirstName = $participantFirstName;
        $this->participantLastName  = $participantLastName;
        $this->proposedParticipants = $proposedParticipants;
    }

    /**
     * @return bool
     */
    public function isSystemSelection(): bool
    {
        return $this->systemSelection;
    }

    /**
     * Textual original first name value of participant
     * 
     * @return string|null
     */
    public function getRelatedFirstName(): ?string
    {
        return $this->relatedFirstName;
    }

    /**
     * Textual original last name value of participant
     * 
     * @return string|null
     */
    public function getRelatedLastName(): ?string
    {
        return $this->relatedLastName;
    }

    /**
     * Aid of {@see Participant} if one was selected for this user
     *
     * @return int|null
     */
    public function getParticipantAid(): ?int
    {
        return $this->participantAid;
    }

    /**
     * First name of {@see Participant} if one was selected for this user
     *
     * @return string|null
     */
    public function getParticipantFirstName(): ?string
    {
        return $this->participantFirstName;
    }

    /**
     * Last name of {@see Participant} if one was selected for this user
     * 
     * @return string|null
     */
    public function getParticipantLastName(): ?string
    {
        return $this->participantLastName;
    }

    /**
     * @return array|null
     */
    public function getProposedParticipants(): ?array
    {
        return $this->proposedParticipants;
    }

    /**
     * @param bool $systemSelection
     */
    public function setIsSystemSelection(bool $systemSelection): void
    {
        $this->systemSelection = $systemSelection;
    }

    /**
     * @param string|null $relatedFirstName
     */
    public function setRelatedFirstName(?string $relatedFirstName): void
    {
        if ($this->relatedFirstName !== $relatedFirstName) {
            $this->resetSystemSelection();
        }
        $this->relatedFirstName = $relatedFirstName;
    }

    /**
     * @param string|null $relatedLastName
     */
    public function setRelatedLastName(?string $relatedLastName): void
    {
        if ($this->relatedLastName !== $relatedLastName) {
            $this->resetSystemSelection();
        }
        $this->relatedLastName = $relatedLastName;
    }

    /**
     * @param int|null $participantAid
     */
    public function setParticipantAid(?int $participantAid): void
    {
        $this->participantAid = $participantAid;
    }

    /**
     * If this field has a system selection configured, reset it
     *
     * @return void
     */
    private function resetSystemSelection(): void
    {
        if ($this->systemSelection) {
            $this->systemSelection      = false;
            $this->participantAid       = null;
            $this->participantFirstName = null;
            $this->participantLastName  = null;
            $this->proposedParticipants = null;
        }
    }

    /**
     * @param string|null $participantFirstName
     */
    public function setParticipantFirstName(?string $participantFirstName): void
    {
        $this->participantFirstName = $participantFirstName;
    }

    /**
     * @param string|null $participantLastName
     */
    public function setParticipantLastName(?string $participantLastName): void
    {
        $this->participantLastName = $participantLastName;
    }

    /**
     * @param array|null $proposedParticipants
     */
    public function setProposedParticipants(?array $proposedParticipants): void
    {
        $this->proposedParticipants = $proposedParticipants;
    }

    /**
     * @return string
     */
    public static function getType(): string
    {
        return self::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormValue(): self
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormValue($value): void
    {
        if ($value === null) {
            $this->systemSelection      = false;
            $this->participantAid       = null;
            $this->relatedFirstName     = null;
            $this->relatedLastName      = null;
            $this->participantFirstName = null;
            $this->participantLastName  = null;
            $this->proposedParticipants = null;
        } elseif ($value instanceof self) {
            $this->systemSelection      = $value->isSystemSelection();
            $this->participantAid       = $value->getParticipantAid();
            $this->relatedFirstName     = $value->getRelatedFirstName();
            $this->relatedLastName      = $value->getRelatedLastName();
            $this->participantFirstName = $value->getParticipantFirstName();
            $this->participantLastName  = $value->getParticipantLastName();
            $this->proposedParticipants = $value->getProposedParticipants();
        } else {
            throw new \InvalidArgumentException('Object of unexpected class provided');
        }
    }


    /**
     * @return string
     */
    public function getTextualValue(): string
    {
        return (string)$this->relatedFirstName . ' ' . $this->relatedLastName;
    }

    /**
     * @return array[]
     */
    public function jsonSerialize(): array
    {
        return [
            self::KEY_VALUE => [
                self::KEY_SYSTEM_SELECTION                 => $this->systemSelection,
                self::KEY_SELECTED_AID                     => $this->participantAid,
                self::KEY_PARTICIPANT_DETECTING_LAST_NAME  => $this->relatedLastName,
                self::KEY_PARTICIPANT_DETECTING_FIRST_NAME => $this->relatedFirstName,
                self::KEY_PARTICIPANT_SELECTED_LAST_NAME   => $this->participantLastName,
                self::KEY_PARTICIPANT_SELECTED_FIRST_NAME  => $this->participantFirstName,
                self::KEY_PROPOSED_PARTICIPANTS            => $this->proposedParticipants,
            ],
        ];
    }

    /**
     * Determine if this value is equal to transmitted one
     *
     * @param CustomFieldValueInterface $other
     * @return bool
     */
    public function isEqualTo(CustomFieldValueInterface $other): bool
    {
        return (
            $other instanceof self
            && $this->isSystemSelection() === $other->isSystemSelection()
            && $this->getParticipantAid() === $other->getParticipantAid()
            && $this->getRelatedLastName() === $other->getRelatedLastName()
            && $this->getParticipantLastName() === $other->getParticipantLastName()
            && $this->getRelatedFirstName() === $other->getRelatedFirstName()
            && $this->getParticipantFirstName() === $other->getParticipantFirstName()
        );
    }
}
