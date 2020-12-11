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


use AppBundle\Manager\Filesharing\WebDavApi\NextcloudWebDavConnector;

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
     * Extract user name
     *
     * @return string
     */
    public function getUsername(): string
    {
        $matches = $this->extractHrefParts();
        return $matches['username'];
    }
    
    /**
     * Extract path, url encoded
     *
     * @param bool $urldecode
     * @return string[]
     */
    public function getPath(bool $urldecode = false): array
    {
        $matches = $this->extractHrefParts();
        $path    = explode('/', $matches['path']);
        if ($urldecode) {
            foreach ($path as &$pathPart) {
                $pathPart = urldecode(urldecode($pathPart));
            }
            unset($pathPart);
        }
        return $path;
    }
    
    /**
     * @return array
     */
    private function extractHrefParts(): array
    {
        $pattern = '/^' . preg_quote(NextcloudWebDavConnector::API_PATH, '/') .
                   '(?<username>[^\/]+)\/(?<path>.+?)(?:[\/]{0,1})$/';
        if (preg_match($pattern, $this->href, $matches)) {
            return $matches;
        }
        throw new \RuntimeException('Failed to extract ' . $this->href);
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