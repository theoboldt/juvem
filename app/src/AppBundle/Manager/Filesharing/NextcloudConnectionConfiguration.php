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


class NextcloudConnectionConfiguration
{

    /**
     * Base URI to nextcloud provider, including protocol
     *
     * @var string
     */
    private string $baseUri;

    /**
     * Admin user name on whose behalf operations are made
     *
     * @var string
     */
    private string $username;

    /**
     * Password to be used for admin account
     *
     * @var string
     */
    private string $password;

    /**
     * Name of events root folder
     *
     * @var string
     */
    private string $folder;

    /**
     * NextcloudConnectionConfiguration constructor.
     *
     * @param string $baseUri
     * @param string $username
     * @param string $password
     * @param string $folder
     */
    public function __construct(string $baseUri, string $username, string $password, $folder = 'Events')
    {
        $this->baseUri  = rtrim($baseUri, '/');
        $this->username = $username;
        $this->password = $password;
        $this->folder   = $folder;
    }

    /**
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }
}
