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


class NextcloudDirectory extends AbstractNextcloudFileItem implements NextcloudFileInterface
{
    
    /**
     * Get directory name
     *
     * @return string
     */
    public function getName(): string
    {
        if (preg_match('!/([^/]*)/$!', $this->getHref(true), $matches)) {
            return $matches[1];
        }
        throw new \InvalidArgumentException('Failed to extract name of ' . $this->getHref(true));
    }
    
    /**
     * Extract directory name for WebDAV urlencoded Href
     *
     * @param string $urlencodedHref Href source
     * @return string Directory name
     */
    public static function extractDirectoryNameFromDirectoryHref(string $urlencodedHref): string
    {
        if (preg_match('!/([^/]*)/$!', urldecode(urldecode($urlencodedHref)), $matches)) {
            return $matches[1];
        }
        throw new \InvalidArgumentException('Failed to extract name for ' . $urlencodedHref);
    }
}