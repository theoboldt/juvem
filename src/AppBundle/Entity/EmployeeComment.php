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


use AppBundle\Entity\Audit\CreatorModifierTrait;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="employee_comment")
 * @ORM\HasLifecycleCallbacks()
 */
class EmployeeComment extends CommentBase
{
    use CreatedModifiedTrait, SoftDeleteTrait, CreatorModifierTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Employee", inversedBy="comments")
     * @ORM\JoinColumn(name="gid", referencedColumnName="gid", onDelete="cascade")
     *
     * @var Employee
     */
    protected $employee;

    /**
     * Get related @see Employee
     *
     * @return Employee
     */
    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    /**
     * @param Employee $employee
     * @return EmployeeComment
     */
    public function setEmployee(Employee $employee)
    {
        $this->employee = $employee;
        return $this;
    }

    /**
     * Get related objects id
     *
     * @return string
     */
    public function getRelatedId()
    {
        return $this->getEmployee()->getGid();
    }
}
