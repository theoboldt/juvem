<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Export\Sheet\Column;


use AppBundle\Entity\Participant;

class AttendanceListColumn extends CallableAccessingColumn
{
    /**
     * List column
     *
     * @var \AppBundle\Entity\AttendanceList\AttendanceListColumn
     */
    private $listColumn;
    
    /**
     * AttendanceListColumn constructor.
     *
     * @param string $identifier
     * @param string $title
     * @param \AppBundle\Entity\AttendanceList\AttendanceListColumn $listColumn Real data column
     * @param array $attendanceData                                             Attendance data
     */
    public function __construct(
        string $identifier,
        string $title,
        \AppBundle\Entity\AttendanceList\AttendanceListColumn $listColumn,
        array $attendanceData
    )
    {
        $this->listColumn = $listColumn;
        $listColumnId     = $listColumn->getColumnId();
        $choices          = $listColumn->getChoices();
        
        $accessor = function (Participant $entity) use ($choices, $listColumnId, $attendanceData) {
            if (isset($attendanceData[$entity->getAid()]['columns'][$listColumnId])) {
                $choiceId = $attendanceData[$entity->getAid()]['columns'][$listColumnId]['choice_id'];
                if (!$choiceId) {
                    return '';
                }
                foreach ($choices as $choice) {
                    if ($choice->getChoiceId() === $choiceId) {
                        return $choice->getShortTitle(true);
                    }
                }
            }
            
            return '';
        };
        parent::__construct($identifier, $title, $accessor);
    }
    
    /**
     * @return \AppBundle\Entity\AttendanceList\AttendanceListColumn
     */
    public function getListColumn(): \AppBundle\Entity\AttendanceList\AttendanceListColumn
    {
        return $this->listColumn;
    }
    
}