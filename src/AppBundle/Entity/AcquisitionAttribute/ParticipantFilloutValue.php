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


use AppBundle\Entity\Participant;
use AppBundle\Form\ParticipantDetectingType;

class ParticipantFilloutValue extends FilloutValue
{

    const KEY_PROPOSED_IDS = 'proposed_aids';

    /**
     * True if @see $rawValue is processed
     *
     * @var bool
     */
    private $processed = false;

    /**
     * Textual original first name value of participant
     *
     * @var string|null
     */
    private $relatedFirstName;

    /**
     * Textual original last name value of participant
     *
     * @var string|null
     */
    private $relatedLastName;

    /**
     * Aid of @see Participant if one was selected for this user
     *
     * @var int|null
     */
    private $participantAid = null;

    /**
     * First name of @see Participant
     *
     * @var null|string
     */
    private $participantFirstName = null;

    /**
     * Last name of @see Participant
     *
     * @var null|string
     */
    private $participantLastName = null;

    /**
     * List of @see Participant ids which are proposed to be related to this user
     *
     * List of @see Participant ids which are proposed to be related to this user. @see null if not initialized
     *
     * @var array|int[]|null
     */
    private $proposedParticipants = null;

    /**
     * Create duplicate, having transmitted participant selected
     *
     * @param Participant $participant Participant to select
     * @return ParticipantFilloutValue New instance
     */
    public function createWithParticipantSelected(Participant $participant)
    {
        $value = json_decode($this->getRawValue(), true);
        if (!is_array($value)) {
            $value = [];
        }
        $value['participant_selected']            = $participant->getAid();
        $value['participant_selected_first_name'] = $participant->getNameFirst();
        $value['participant_selected_last_name']  = $participant->getNameLast();
        return new self($this->getAttribute(), json_encode($value));
    }

    /**
     * Transform fillout to string; Useful for textual display in ui
     *
     * Transform fillout to string; Useful for textual display in ui. Will return label of selected item if
     * this fillout belongs to a choice field
     *
     * @return string
     */
    public function getTextualValue()
    {
        $this->ensureProcessed();
        return (string)$this->relatedFirstName . ' ' . $this->relatedLastName;
    }

    /**
     * Get related @see Participant first name
     *
     * @return string|null
     */
    public function getRelatedFirstName(): ?string
    {
        $this->ensureProcessed();
        return $this->relatedFirstName;
    }

    /**
     * Get related @see Participant last name
     *
     * @return string|null
     */
    public function getRelatedLastName(): ?string
    {
        $this->ensureProcessed();
        return $this->relatedLastName;
    }


    /**
     * Get selected @see Participant id
     *
     * @return int|null
     */
    public function getSelectedParticipantId()
    {
        $this->ensureProcessed();
        return $this->participantAid;
    }

    /**
     * Get selected @see Participant first name
     *
     * @return string|null
     */
    public function getSelectedParticipantFirstName() {
        $this->ensureProcessed();
        return $this->participantFirstName;
    }

    /**
     * Get selected @see Participant last name
     *
     * @return string|null
     */
    public function getSelectedParticipantLastName() {
        $this->ensureProcessed();
        return $this->participantLastName;
    }

    /**
     * Get proposed @see Participant ids
     *
     * @return array|int[]
     */
    public function getProposedParticipantIds(): array
    {
        $this->ensureProcessed();
        if (!is_array($this->proposedParticipants)) {
            throw new \InvalidArgumentException('No proposals available');
        }
        return $this->proposedParticipants;
    }

    /**
     * Determine if proposed @see Participant were calculated
     *
     * @return bool
     */
    public function hasProposedParticipantsCalculated(): bool
    {
        $this->ensureProcessed();
        return is_array($this->proposedParticipants);
    }

    /**
     * Process data
     *
     * @return void
     */
    private function ensureProcessed()
    {
        if ($this->processed) {
            return;
        }
        $this->processed = true;

        if (!$this->rawValue) {
            return;
        }
        $value = json_decode($this->rawValue, true);
        if (is_array($value)) {
            if (isset($value[ParticipantDetectingType::FIELD_NAME_FIRST_NAME])) {
                $this->relatedFirstName = $value[ParticipantDetectingType::FIELD_NAME_FIRST_NAME];
            }
            if (isset($value[ParticipantDetectingType::FIELD_NAME_LAST_NAME])) {
                $this->relatedLastName = $value[ParticipantDetectingType::FIELD_NAME_LAST_NAME];
            }
            if (isset($value['participant_selected'])) {
                $this->participantAid = $value['participant_selected'];
            }
            if (isset($value['participant_selected_first_name'])) {
                $this->participantFirstName = $value['participant_selected_first_name'];
            }
            if (isset($value['participant_selected_last_name'])) {
                $this->participantLastName  = $value['participant_selected_last_name'];
            }
            if (isset($value[self::KEY_PROPOSED_IDS])) {
                $this->proposedParticipants = $value[self::KEY_PROPOSED_IDS];
            }
        } else {
            $this->relatedFirstName = $this->rawValue;
        }
    }

    /**
     * Get json decoded value as array
     *
     * @return array
     */
    public function getFormValue(): array
    {
        $this->ensureProcessed();
        return [
            ParticipantDetectingType::FIELD_NAME_FIRST_NAME => $this->relatedFirstName,
            ParticipantDetectingType::FIELD_NAME_LAST_NAME  => $this->relatedLastName,
        ];
    }

}
