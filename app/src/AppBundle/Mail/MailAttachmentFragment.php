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
 * @Serialize\ReadOnlyProperty()
 */
class MailAttachmentFragment
{
    
    /**
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @var string
     */
    private string $partNumber;
    
    /**
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @var string|null
     */
    private ?string $filename;
    
    
    /**
     * @Serialize\Expose
     * @Serialize\Type("integer")
     * @var int|null
     */
    private ?int $filesize;
    
    /**
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @var string|null
     */
    private ?string $type;
    
    /**
     * @Serialize\Expose
     * @Serialize\Type("string")
     * @var string|null
     */
    private ?string $subtype;
    
    /**
     * @param string $partNumber
     * @param string|null $filename
     * @param int|null $filesize
     * @param string|null $type
     * @param string|null $subtype
     */
    public function __construct(string $partNumber, ?string $filename, ?int $filesize, ?string $type, ?string $subtype)
    {
        $this->partNumber = $partNumber;
        $this->filename   = $filename;
        $this->filesize   = $filesize;
        $this->type       = $type;
        $this->subtype    = $subtype;
    }
    
    /**
     * @return string
     */
    public function getPartNumber(): string
    {
        return $this->partNumber;
    }
    
    /**
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }
    
    /**
     * @return int|null
     */
    public function getFilesize(): ?int
    {
        return $this->filesize;
    }
    
    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }
    
    /**
     * @return string|null
     */
    public function getSubtype(): ?string
    {
        return $this->subtype;
    }
}
