<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Calendar;


class CalendarConnectionConfiguration
{

    /**
     * Base URI to calendar, including protocol
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
     * Public calendar uri if configured
     * 
     * @var string|null 
     */
    private ?string $publicUri;

    /**
     * NextcloudConnectionConfiguration constructor.
     *
     * @param string      $baseUri
     * @param string      $username
     * @param string      $password
     * @param string|null $publicUri
     */
    public function __construct(string $baseUri, string $username, string $password, ?string $publicUri)
    {
        $this->baseUri   = rtrim($baseUri, '/') . '/';
        $this->username  = $username;
        $this->password  = $password;
        $this->publicUri = $publicUri;
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
     * @return string|null
     */
    public function getPublicUri(): ?string
    {
        return $this->publicUri;
    }

    /**
     * @return bool
     */
    public function hasPublicUri(): bool
    {
        return $this->publicUri !== null;
    } 
}
