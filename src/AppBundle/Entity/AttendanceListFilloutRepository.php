<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class AttendanceListFilloutRepository extends EntityRepository
{

    /**
     * Fetch fillouts by aid and tid
     *
     * @param  Participant    $participant       Participant id
     * @param  AttendanceList $list              Attendance List id
     * @param  bool           $createIfNotExists Set to true to automatically create such an entry
     * @return AttendanceListFillout|null
     */
    public function findFillout(Participant $participant, AttendanceList $list, $createIfNotExists = false)
    {
        $fillout = $this->findOneBy(['participant' => $participant, 'attendanceList' => $list]);
        if (!$fillout) {
            if ($createIfNotExists) {
                $fillout = new AttendanceListFillout();
            } else {
                return null;
            }
        }
        $fillout->setParticipant($participant);
        $fillout->setAttendanceList($list);

        return $fillout;
    }
}
