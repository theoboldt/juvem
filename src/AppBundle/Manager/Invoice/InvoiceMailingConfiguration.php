<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Invoice;


use AppBundle\Entity\Event;
use AppBundle\Entity\Invoice;

class InvoiceMailingConfiguration
{
    const SEND_ALL_FILTER = 'send_most_recent_resend';
    const SEND_ALL_LABEL  = 'Die aktuellste Rechnung für alle Teilnehmer verschicken, unabhängig davon, ob sie bereits verschickt wurden';

    const SEND_NEW_FILTER = 'send_most_recent_unsend';
    const SEND_NEW_LABEL  = 'Die aktuellste Rechnung für alle Teilnehmer verschicken, die bisher noch nicht verschickt wurden';

    const FILE_TYPE_PDF       = 'file_type_pdf';
    const FILE_TYPE_PDF_LABEL = 'Rechnungen als PDF-Datei anhängen';

    const FILE_TYPE_WORD       = 'file_type_word';
    const FILE_TYPE_WORD_LABEL = 'Rechnungen als Word-Datei anhängen';

    /**
     * Related event where {@see Invoice} should be mailed
     *
     * @var Event
     */
    private $event;

    /**
     * Filter for invoices
     *
     * @var string
     */
    private $filter;

    /**
     * File type to use for sending
     *
     * @var string
     */
    private $fileType;

    /**
     * Message for email
     *
     * @var string
     */
    private $message = '';

    /**
     * InvoiceMailingConfiguration constructor.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event    = $event;
        $this->filter   = self::SEND_NEW_FILTER;
        $this->fileType = self::FILE_TYPE_WORD;
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * @return string
     */
    public function getFilter(): string
    {
        return $this->filter;
    }

    /**
     * @param string $filter
     */
    public function setFilter(string $filter): void
    {
        if (!in_array($filter, [self::SEND_ALL_FILTER, self::SEND_NEW_FILTER])) {
            throw new \InvalidArgumentException(sprintf('Unknown filter "%s" transmitted', $filter));
        }
        $this->filter = $filter;
    }

    /**
     * @return string
     */
    public function getFileType(): string
    {
        return $this->fileType;
    }

    /**
     * @param string $fileType
     */
    public function setFileType(string $fileType): void
    {
        if (!in_array($fileType, [self::FILE_TYPE_PDF, self::FILE_TYPE_WORD])) {
            throw new \InvalidArgumentException(sprintf('Unknown file type "%s" transmitted', $fileType));
        }
        $this->fileType = $fileType;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
