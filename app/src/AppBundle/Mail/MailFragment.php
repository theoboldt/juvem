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
     * MailFragment constructor.
     *
     * @param string[] $from
     * @param string[] $to
     * @param string $subject
     * @param \DateTimeImmutable $date
     * @param string $mailbox
     */
    public function __construct(array $from, array $to, string $subject, \DateTimeImmutable $date, string $mailbox)
    {
        $this->from    = $from;
        $this->to      = $to;
        $this->subject = $subject;
        $this->date    = $date;
        $this->mailbox = $mailbox;
    }
}