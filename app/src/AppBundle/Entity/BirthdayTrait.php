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


trait BirthdayTrait
{
    /**
     * Birthday of person
     *
     * @var \DateTime
     */
    protected $birthday;
    
    /**
     * Set birthday
     *
     * @param \DateTime $birthday
     *
     * @return BirthdayTrait
     */
    public function setBirthday($birthday)
    {
        if ($birthday) {
            $birthday = clone $birthday;
            $birthday->setTime(10, 00);
        }
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        if ($this->birthday) {
            $this->birthday->setTime(10, 00);
        }
        return $this->birthday;
    }

    /**
     * Get age of participant at the related event
     *
     * @param int|null $precision If you want the result to be rounded with round(), specify precision here
     * @return float              Age in years
     */
    public function getAgeAtEvent($precision = null): float
    {
        $event = $this->getEvent();
        return EventRepository::age($this->getBirthday(), $event->getStartDate(), $precision);
    }

    /**
     * Get age of participant at the related event
     *
     * @return int Amount of years of life at event
     */
    public function getYearsOfLifeAtEvent(): int
    {
        $event = $this->getEvent();
        return EventRepository::yearsOfLife($this->getBirthday(), $event->getStartDate());
    }

    /**
     * Check if participant has birthday at related event
     *
     * @return bool True if so
     */
    public function hasBirthdayAtEvent()
    {
        $event = $this->getEvent();
        return EventRepository::hasBirthdayInTimespan(
            $this->getBirthday(), $event->getStartDate(), $event->getEndDate()
        );
    }
    
    /**
     * Expected to have event assigned
     *
     * @return Event|null
     */
    abstract function getEvent(): ?Event;
}
