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


interface NextcloudFileInterface
{
    /**
     * @param bool $urldecode If set to true, provide urldecoded
     * @return string
     */
    public function getHref(bool $urldecode = false): string;
    
    /**
     * Get directory/file name
     *
     * @return string
     */
    public function getName(): string;
    
    /**
     * @return \DateTimeImmutable
     */
    public function getLastModified(): \DateTimeImmutable;
    
    /**
     * @return int
     */
    public function getFileId(): int;
    
    /**
     * @return int
     */
    public function getSize(): int;
    
    /**
     * @return string
     */
    public function getEtag(): string;
}