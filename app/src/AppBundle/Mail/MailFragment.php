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
     * Message number
     *
     * @Serialize\Expose
     * @Serialize\Type("integer")
     * @var int|null
     */
    private ?int $number = null;
    
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
     *
     * @var MailAttachmentFragment[]
     */
    private array $attachments;
    
    /**
     * MailFragment constructor.
     *
     * @param int|null $number
     * @param string[] $from
     * @param string[] $to
     * @param string $subject
     * @param \DateTimeImmutable $date
     * @param string $mailbox
     * @param array $attachments
     */
    public function __construct(
        ?int               $number,
        array              $from,
        array              $to,
        string             $subject,
        \DateTimeImmutable $date,
        string             $mailbox,
        array              $attachments
    )
    {
        $this->number      = $number;
        $this->from        = $from;
        $this->to          = $to;
        $this->subject     = $subject;
        $this->date        = $date;
        $this->mailbox     = $mailbox;
        $this->attachments = $attachments;
    }
    
    /**
     * @return int|null
     */
    public function getNumber(): ?int
    {
        return $this->number;
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
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @Serialize\VirtualProperty()
     * @Serialize\SerializedName("mailbox_symbol")
     * @return null|string
     */
    public function getMailboxSymbol(): ?string
    {
        switch (mb_strtolower($this->mailbox)) {
            case 'posteingang':
            case 'inbox':
                return 'log-in';
            case 'sent':
                case 'gesendete objekte';
                case 'gesendet';
                return 'log-out';
            default:
                return 'unchecked';
        }
        return null;
    }
    
    /**
     * @Serialize\Expose
     * @Serialize\Type("integer")
     * @Serialize\VirtualProperty()
     * @Serialize\SerializedName("attachment_count")
     * @return int
     */
    public function getAttachmentCount(): int
    {
        return count($this->attachments);
    }
    
    /**
     * @return MailAttachmentFragment[]
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }
    
    
}