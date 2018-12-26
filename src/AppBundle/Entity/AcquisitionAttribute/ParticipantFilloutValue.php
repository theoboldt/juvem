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
     * List of @see Participant ids which were deselected for this user
     *
     * @var array|int[]
     */
    private $deselectedParticipants = [];

    /**
     * List of @see Participant ids which are proposed to be related to this user
     *
     * List of @see Participant ids which are proposed to be related to this user. @see null if not initialized
     *
     * @var array|int[]|null
     */
    private $proposedParticipants = null;

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
     * Get proposed @see Participant ids
     *
     * @return array|int[]
     */
    public function getProposedParticipantIds(): array
    {
        $this->ensureProcessed();
        if (is_array($this->proposedParticipants)) {
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
     * Get deselected @see Participant
     *
     * @return array|int[]
     */
    public function getDeselectedParticipantIds(): array
    {
        $this->ensureProcessed();
        return $this->deselectedParticipants;
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
            if (isset($this->rawValue[ParticipantDetectingType::FIELD_NAME_FIRST_NAME])) {
                $this->relatedFirstName = $this->rawValue[ParticipantDetectingType::FIELD_NAME_FIRST_NAME];
            }
            if (isset($this->rawValue[ParticipantDetectingType::FIELD_NAME_LAST_NAME])) {
                $this->relatedLastName = $this->rawValue[ParticipantDetectingType::FIELD_NAME_LAST_NAME];
            }
            if (isset($this->rawValue['participant_selected'])) {
                $this->participantAid = $this->rawValue['participant_selected'];
            }
            if (isset($this->rawValue['not_aids'])) {
                $this->deselectedParticipants = $this->rawValue['participant_deselected'];
            }
            if (isset($this->rawValue['proposed_aids'])) {
                $this->proposedParticipants = $this->rawValue['proposed_aids'];
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
