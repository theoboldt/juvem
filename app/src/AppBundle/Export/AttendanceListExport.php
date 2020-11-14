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
use AppBundle\Twig\GlobalCustomizationConfigurationProvider;

class AttendanceListExport extends Export
{

    /**
     * The attendance list (containing event)
     *
     * @var array|AttendanceList[]
     */
    protected $lists = [];

    /**
     * Stores a list of Participant entities
     *
     * @var array
     */
    protected $participants = [];

    /**
     * Filed attendance list data
     *
     * @var array
     */
    private $attendanceData = [];
    /**
     * Group field
     *
     * @var Attribute|null
     */
    private $groupBy;

    /**
     * ParticipationsExport constructor.
     *
     * @param GlobalCustomizationConfigurationProvider $customization  Customization provider in order to eg. add
     *                                                                 company information
     * @param array|AttendanceList[]                   $lists          Attendance lists to export
     * @param array                                    $participants   List of participants qualified for export
     * @param array                                    $attendanceData Filed attendance list data
     * @param User|null                                $modifier       Modifier/creator of export
     * @param Attribute|null                           $groupBy        Group field
     */
    public function __construct(
        GlobalCustomizationConfigurationProvider $customization,
        array $lists,
        array $participants,
        array $attendanceData,
        User $modifier,
        ?Attribute $groupBy = null
    ) {
        $this->lists = $lists;
        if (!count($this->lists)) {
            throw new \InvalidArgumentException('Must provide multiple lists for export');
        }
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

        $titles     = [];
        $eventTitle = '';

        foreach ($this->lists as $list) {
            $titles[]   = $list->getTitle();
            $eventTitle = $list->getEvent()->getTitle();
        }

        $this->document->getProperties()
                       ->setTitle(implode(', ', $titles));
        $this->document->getProperties()
                       ->setSubject($eventTitle);
        $this->document->getProperties()
                       ->setDescription(
                           sprintf(
                               'Anwesenheitsliste "%s" für Veranstaltung "%s"',
                               implode('", "', $titles),
                               $eventTitle
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
            $sheet, $this->lists, $this->participants, $this->attendanceData, $this->groupBy
        );
        $participantsSheet->process();

        $titles     = [];
        foreach ($this->lists as $list) {
            $titles[]   = $list->getTitle();
        }

        $title = implode(', ', $titles);
        if (mb_strlen($title) >= 31) {
            $titleLengthEvenly = mb_strlen($title) / (count($titles) ?: 1);
            $titles            = [];
            foreach ($this->lists as $list) {
                $titles[] = mb_substr($list->getTitle(), 0, $titleLengthEvenly);
            }
            $title = mb_substr(implode(';', $titles), 0, 31);
        }
        $sheet->setTitle($title);

        parent::process();
    }


}
