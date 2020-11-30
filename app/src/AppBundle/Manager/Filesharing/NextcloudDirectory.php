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


class NextcloudDirectory
{
    private string $name;

    private \DateTimeImmutable $lastModified;

    private int $fileId;

    private int $size;

    /**
     * NextcloudDirectory constructor.
     *
     * @param string             $name
     * @param \DateTimeImmutable $lastModified
     * @param int                $fileId
     * @param int                $size
     */
    public function __construct(string $name, \DateTimeImmutable $lastModified, int $fileId, int $size)
    {
        $this->name         = $name;
        $this->lastModified = $lastModified;
        $this->fileId       = $fileId;
        $this->size         = $size;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
}
