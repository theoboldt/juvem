<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Filesharing;


class NextcloudFile extends AbstractNextcloudFileItem implements NextcloudFileInterface
{
    /**
     * @var string|null
     */
    private ?string $contentType;
    
    /**
     * AbstractNextcloudFileItem constructor.
     *
     * @param string $href
     * @param \DateTimeImmutable $lastModified
     * @param int $fileId
     * @param int $size
     * @param string $etag
     * @param string|null $contentType
     */
    public function __construct(
        string $href, \DateTimeImmutable $lastModified, int $fileId, int $size, string $etag, ?string $contentType
    )
    {
        parent::__construct($href, $lastModified, $fileId, $size, $etag);
        $this->contentType = $contentType;
    }
    
    /**
     * Get file name
     *
     * @return string
     */
    public function getName(): string
    {
        if (preg_match('!/([^/]*)$!', $this->getHref(true), $matches)) {
            return $matches[1];
        }
        throw new \InvalidArgumentException('Failed to extract name of ' . $this->getHref(true));
    }
    
    
    /**
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }
    
}