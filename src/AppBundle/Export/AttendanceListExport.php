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


use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AttendanceList\AttendanceList;
use AppBundle\Entity\User;
use AppBundle\Export\Sheet\AttendanceListSheet;
use AppBundle\Twig\GlobalCustomization;

class AttendanceListExport extends Export
{
    
    /**
     * The attendance list (containing event)
     *
     * @var AttendanceList
     */
    protected $list;
    
    /**
     * Stores a list of Participant entities
     *
     * @var array
     */
    protected $participants;
    
    /**
     * Filed attendance list data
     *
     * @var array
     */
    private $attendanceData;
    /**
     * Group field
     *
     * @var Attribute|null
     */
    private $groupBy;
    
    /**
     * ParticipationsExport constructor.
     *
     * @param GlobalCustomization $customization Customization provider in order to eg. add company information
     * @param AttendanceList $list               Attendance list
     * @param array $participants                List of participants qualified for export
     * @param array $attendanceData              Filed attendance list data
     * @param User|null $modifier                Modifier/creator of export
     * @param Attribute|null $groupBy            Group field
     */
    public function __construct(
        $customization,
        AttendanceList $list,
        array $participants,
        array $attendanceData,
        User $modifier,
        ?Attribute $groupBy = null
    )
    {
        $this->list           = $list;
        $this->participants   = $participants;
        $this->attendanceData = $attendanceData;
        $this->groupBy        = $groupBy;
        parent::__construct($customization, $modifier);
    }
    
    /**
     * {@inheritDoc}
     */
    public function setMetadata()
    {
        parent::setMetadata();
        
        $this->document->getProperties()
                       ->setTitle($this->list->getTitle());
        $this->document->getProperties()
                       ->setSubject($this->list->getEvent()->getTitle());
        $this->document->getProperties()
                       ->setDescription(
                           sprintf(
                               'Anwesenheitsliste "%s" für Veranstaltung "%s"', $this->list->getTitle(),
                               $this->list->getEvent()->getTitle()
                           )
                       );
    }
    
    /**
     * {@inheritDoc}
     */
    public function process()
    {
        $sheet = $this->addSheet();
        
        $participantsSheet = new AttendanceListSheet(
            $sheet, $this->list, $this->participants, $this->attendanceData, $this->groupBy
        );
        $participantsSheet->process();
        
        $sheet->setTitle($this->list->getTitle());
        
        parent::process();
    }
    
    
}