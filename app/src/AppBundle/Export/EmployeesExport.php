<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Export;


use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\User;
use AppBundle\Export\Sheet\EmployeesSheet;
use AppBundle\Twig\GlobalCustomizationConfigurationProvider;

class EmployeesExport extends Export
{
    /**
     * The event this participant export belongs to
     *
     * @var Event
     */
    protected $event;

    /**
     * Stores list of {@see Employee} entities
     *
     * @var array|Employee[]
     */
    protected $employees;


    /**
     * EmployeesExport constructor.
     *
     * @param GlobalCustomizationConfigurationProvider $customization Customization provider in order to eg. add
     *                                                                company information
     * @param Event                                    $event         Event to export
     * @param array|Employee[]                         $employees     List of employees qualified for export
     * @param User|null                                $modifier      Modifier/creator of export
     */
    public function __construct(
        GlobalCustomizationConfigurationProvider $customization,
        Event $event,
        array $employees,
        User $modifier
    ) {
        $this->event     = $event;
        $this->employees = $employees;

        parent::__construct($customization, $modifier);
    }

    /**
     * {@inheritDoc}
     */
    public function setMetadata()
    {
        parent::setMetadata();

        $this->document->getProperties()
                       ->setTitle('Mitarbeiter');
        $this->document->getProperties()
                       ->setSubject($this->event->getTitle());
        $this->document->getProperties()
                       ->setDescription(sprintf('Mitarbeiterliste für Veranstaltung "%s"', $this->event->getTitle()));
    }

    /**
     * {@inheritDoc}
     */
    public function process()
    {
        $sheet = $this->addSheet();

        $participantsSheet = new EmployeesSheet($sheet, $this->event, $this->employees);
        $participantsSheet->process();

        $sheet->setTitle('Teilnehmer');

        parent::process();
    }

}
