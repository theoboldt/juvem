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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

abstract class NewsletterAbstract
{
    /**
     * Contains the lower limit of possible values for age range
     */
    const AGE_RANGE_MIN = 0;

    /**
     * Contains the upper limit of possible values for age range
     */
    const AGE_RANGE_MAX = 18;

    /**
     * Contains the lower limit of default value for age range
     */
    const AGE_RANGE_DEFAULT_MIN = 6;

    /**
     * Contains the upper limit of default value for age range
     */
    const AGE_RANGE_DEFAULT_MAX = 16;

    /**
     * @ORM\Column(type="date", name="base_age", nullable=true)
     */
    protected $baseAge;

    /**
     * @ORM\Column(type="smallint", name="age_range_begin", options={"unsigned"=true})
     */
    protected $ageRangeBegin = self::AGE_RANGE_DEFAULT_MIN;

    /**
     * @ORM\Column(type="smallint", name="age_range_end", options={"unsigned"=true})
     */
    protected $ageRangeEnd = self::AGE_RANGE_DEFAULT_MAX;

    /**
     * Contains the list of events this newsletter is about or which are subscribed by users
     *
     * @var array|ArrayCollection
     */
    protected $events;

    /**
     * Construct new newsletter, initialize events collection
     */
    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    /**
     * Set ageRangeBegin
     *
     * @param integer $ageRangeBegin
     *
     * @return self
     */
    public function setAgeRangeBegin($ageRangeBegin)
    {
        $this->ageRangeBegin = $ageRangeBegin;

        return $this;
    }

    /**
     * Get ageRangeBegin
     *
     * @return integer
     */
    public function getAgeRangeBegin()
    {
        return $this->applyAging($this->ageRangeBegin);
    }

    /**
     * Set ageRangeEnd
     *
     * @param integer $ageRangeEnd
     *
     * @return self
     */
    public function setAgeRangeEnd($ageRangeEnd)
    {
        $this->ageRangeEnd = $ageRangeEnd;

        return $this;
    }

    /**
     * Get ageRangeEnd
     *
     * @return integer
     */
    public function getAgeRangeEnd()
    {
        return $this->applyAging($this->ageRangeEnd);
    }

    /**
     * Set baseAge
     *
     * @param \DateTime $baseAge
     *
     * @return NewsletterSubscription
     */
    public function setBaseAge($baseAge)
    {
        $this->baseAge = $baseAge;

        return $this;
    }

    /**
     * Get baseAge
     *
     * @return \DateTime
     */
    public function getBaseAge()
    {
        return $this->baseAge;
    }

    /**
     * Find out wether aging is used or not
     *
     * @return bool
     */
    public function useAging()
    {
        return (bool)$this->getBaseAge();
    }

    /**
     * Define if aging should be used or not
     *
     * @param $value
     * @return NewsletterSubscription
     */
    public function setUseAging($value)
    {
        if ($value) {
            $this->setBaseAge(new \DateTime());
        } else {
            $this->setBaseAge(null);
        }
        return $this;
    }

    /**
     * Apply aging to transmitted age
     *
     * @param   integer $age
     * @return  number
     */
    public function applyAging($age)
    {
        $baseAge = $this->getBaseAge();
        if ($baseAge) {
            $today    = new \DateTime();
            $interval = $today->diff($baseAge);
            $age += abs($interval->format('%y'));
        }

        if ($age < self::AGE_RANGE_MIN) {
            return self::AGE_RANGE_MIN;
        }
        if ($age > self::AGE_RANGE_MAX) {
            return self::AGE_RANGE_MAX;
        }

        return $age;
    }

    /**
     * Add subscribedEvent
     *
     * @param \AppBundle\Entity\Event $subscribedEvent
     *
     * @return NewsletterSubscription
     */
    public function addEvent(\AppBundle\Entity\Event $subscribedEvent)
    {
        $this->events[] = $subscribedEvent;

        return $this;
    }

    /**
     * Remove subscribedEvent
     *
     * @param \AppBundle\Entity\Event $subscribedEvent
     */
    public function removeEvent(\AppBundle\Entity\Event $subscribedEvent)
    {
        $this->events->removeElement($subscribedEvent);
    }

    /**
     * Get events
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEvents()
    {
        return $this->events;
    }

}
