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


abstract class AbstractNextcloudFileItem
{
    private string $href;
    
    private \DateTimeImmutable $lastModified;
    
    private int $fileId;
    
    private int $size;
    
    private string $etag;
    
    /**
     * AbstractNextcloudFileItem constructor.
     *
     * @param string $href
     * @param \DateTimeImmutable $lastModified
     * @param int $fileId
     * @param int $size
     * @param string $etag
     */
    public function __construct(string $href, \DateTimeImmutable $lastModified, int $fileId, int $size, string $etag)
    {
        $this->href         = $href;
        $this->lastModified = $lastModified;
        $this->fileId       = $fileId;
        $this->size         = $size;
        $this->etag         = $etag;
    }
    
    /**
     * @param bool $urldecode
     * @return string
     */
    public function getHref(bool $urldecode = false): string
    {
        if ($urldecode) {
            return urldecode(urldecode($this->href));
        }
        return $this->href;
    }
    
    /**
     * @return \DateTimeImmutable
     */
    public function getLastModified(): \DateTimeImmutable
    {
        return $this->lastModified;
    }
    
    /**
     * @return int
     */
    public function getFileId(): int
    {
        return $this->fileId;
    }
    
    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }
    
    /**
     * @return string
     */
    public function getEtag(): string
    {
        return $this->etag;
    }
    
}