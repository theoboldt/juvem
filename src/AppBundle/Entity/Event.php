<?php
namespace AppBundle\Entity;

/**
 * @Entity
 * @Table(name="event")
 */
class Event
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $eid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $title;

    /**
     * @ORM\Column(type="text")
     */
    protected $description;

    /**
     * @Column(type="datetime", name="start_date")
     */
    protected $startDate;

    /**
     * @Column(type="datetime", name="end_date", nullable)
     */
    protected $endDate;

    /**
     * @Column(type="boolean", name="is_active")
     */
    protected $isActive;

    /**
     * @Column(type="boolean", name="is_visible")
     */
    protected $isVisible;

}