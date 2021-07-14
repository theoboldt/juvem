<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Mail;

use JMS\Serializer\Annotation as Serialize;

/**
 * MailFragment
 *
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\ReadOnly()
 */
class MailFragment
{
    
    /**
     * @Serialize\Expose
     * @Serialize\Type("array<string>")
     *
     * @var string[]
     */
    private array $from = [];
    
    
    /**
     * @Serialize\Expose
     * @Serialize\Type("array<string>")
     *
     * @var string[]
     */
    private array $to = [];
    
    /**
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @var string
     */
    private string $subject;
    
    /**
     * @Serialize\Expose
     * @Serialize\Type("DateTimeImmutable<'d.m.Y H:i:s'>")
     *
     * @var \DateTimeImmutable
     */
    private \DateTimeImmutable $date;
    
    /**
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @var string
     */
    private string $mailbox;
    
    
    /**
     * @Serialize\Expose
     * @Serialize\Type("integer")
     * @var int
     */
    private int $attachmentCount;
    
    /**
     * MailFragment constructor.
     *
     * @param string[] $from
     * @param string[] $to
     * @param string $subject
     * @param \DateTimeImmutable $date
     * @param string $mailbox
     * @param int $attachmentCount
     */
    public function __construct(array $from, array $to, string $subject, \DateTimeImmutable $date, string $mailbox,
                                int $attachmentCount
    )
    {
        $this->from            = $from;
        $this->to              = $to;
        $this->subject         = $subject;
        $this->date            = $date;
        $this->mailbox         = $mailbox;
        $this->attachmentCount = $attachmentCount;
    }
    
    /**
     * @return string[]
     */
    public function getFrom(): array
    {
        return $this->from;
    }
    
    /**
     * @return string[]
     */
    public function getTo(): array
    {
        return $this->to;
    }
    
    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }
    
    /**
     * @return \DateTimeImmutable
     */
    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }
    
    /**
     * @return string
     */
    public function getMailbox(): string
    {
        return $this->mailbox;
    }
    
    /**
     * @return int
     */
    public function getAttachmentCount(): int
    {
        return $this->attachmentCount;
    }
    
    
}