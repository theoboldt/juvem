<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Audit;

use AppBundle\Entity\ChangeTracking\SupportsChangeTrackingInterface;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;

class AuditEvent implements AuditEventInterface
{

    const TYPE_CREATE = 'create';

    const TYPE_MODIFY = 'modify';

    const TYPE_DELETE = 'delete';

    const TYPE_COMMENT_CREATE = 'comment_create';

    const TYPE_COMMENT_MODIFY = 'comment_modify';

    const TYPE_COMMENT_DELETE = 'comment_delete';
    /**
     * @var string
     */
    private string $relatedClass;

    /**
     * @var int
     */
    private int $relatedId;

    /**
     * @var string
     */
    private string $type;

    /**
     * @var \DateTimeImmutable
     */
    private \DateTimeImmutable $occurrenceDate;

    /**
     * @var string
     */
    private string $title;

    /**
     * @param string             $relatedClass
     * @param int                $relatedId
     * @param string             $type
     * @param \DateTimeImmutable $occurrenceDate
     * @param string             $title
     */
    public function __construct(
        string             $relatedClass,
        int                $relatedId,
        string             $type,
        \DateTimeImmutable $occurrenceDate,
        string             $title
    ) {
        $this->relatedClass   = $relatedClass;
        $this->relatedId      = $relatedId;
        $this->type           = $type;
        $this->occurrenceDate = $occurrenceDate;
        $this->title          = $title;
    }


    /**
     * @param SupportsChangeTrackingInterface $object
     * @param string                          $type
     * @param \DateTimeImmutable              $occurrenceDate
     * @return static
     */
    public static function create(
        SupportsChangeTrackingInterface $object,
        string                          $type,
        \DateTimeInterface              $occurrenceDate
    ): self {
        //todo should be improved later
        $class = str_replace('Proxies\__CG__\\', '', get_class($object));

        if ($object instanceof Participant) {
            $title = $object->fullname();
        } elseif ($object instanceof Participation) {
            $title = $object->fullname();
        } elseif ($object instanceof Employee) {
            $title = $object->fullname();
        } else {
            $title = $class . ':' . $object->getId();
        }

        if ($occurrenceDate instanceof \DateTime) {
            $occurrenceDate = \DateTimeImmutable::createFromInterface($occurrenceDate);
        } elseif (!$occurrenceDate instanceof \DateTimeImmutable) {
            throw new \InvalidArgumentException('Unknown date class transmitted');
        }

        return new self(
            $class,
            $object->getId(),
            $type,
            $occurrenceDate,
            $title
        );
    }


    /**
     * @return string
     */
    public function getRelatedClass(): string
    {
        return $this->relatedClass;
    }

    /**
     * @return int
     */
    public function getRelatedId(): int
    {
        return $this->relatedId;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTypeLabel(): string
    {
        switch ($this->getType()) {
            case self::TYPE_CREATE:
                return 'Datensatz erstellt';
            case self::TYPE_MODIFY:
                return 'Datensatz geändert';
            case self::TYPE_DELETE:
                return 'Datensatz gelöscht';
            case self::TYPE_COMMENT_CREATE:
                return 'Anmerkung hinzugefügt';
            case self::TYPE_COMMENT_MODIFY:
                return 'Anmerkung geändert';
            case self::TYPE_COMMENT_DELETE:
                return 'Anmerkung gelöscht';
            default:
                return $this->getType();
        }
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getOccurrenceDate(): \DateTimeImmutable
    {
        return $this->occurrenceDate;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }


}
