<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Juvimg;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Imagine\Image\ImageInterface;
use Psr\Http\Message\StreamInterface;

class JuvimgService
{

    /**
     * URL to juvimg installation
     *
     * @var string
     */
    private $url;

    /**
     * Password configured in juvimg for Basic authentication
     *
     * @var string
     */
    private $password;

    /**
     * True if accessible, false if not, null if not tested
     *
     * @var null|bool
     */
    private $accessible = null;

    /**
     * Cached client
     *
     * @var Client
     */
    private $client;

    /**
     * Create instance if configuration is valid
     *
     * @param string|null $url      URL to juvimg installation
     * @param string|null $password Password configured in juvimg for Basic authentication
     * @return JuvimgService|null   Service or null if not valid
     */
    public static function create(string $url = null, string $password = null)
    {
        if ($url && $password) {
            return new self($url, $password);
        } else {
            return null;
        }
    }

    /**
     * JuvimgService constructor.
     *
     * @param string $url      URL to juvimg installation
     * @param string $password Password configured in juvimg for Basic authentication
     */
    public function __construct(string $url, string $password)
    {
        $this->url      = rtrim($url, '/');
        $this->password = $password;
    }

    /**
     * Resize an image using juvimg
     *
     * @param string $path
     * @param int    $width
     * @param int    $height
     * @param string $mode
     * @param int    $quality
     * @return StreamInterface
     */
    public function resize(
        string $path, int $width, int $height, string $mode = ImageInterface::THUMBNAIL_INSET, int $quality = 70
    )
    {
        if ($this->accessible === false) {
            throw new JuvimgUnaccessibleException('Can not resize image because Juvimg is unaccessible');
        }

        try {
            $result = $this->client()->post(
                $this->getResizeUrl($width, $height, $mode, $quality),
                ['body' => fopen($path, 'r')]
            );
        } catch (RequestException $e) {
            throw new JuvimgImageResizeFailedException('Failed to resize image', $e->getCode(), $e);
        }
        $this->accessible = true;

        return $result->getBody();
    }

    /**
     * Determine if service is accessible
     *
     * @return bool
     */
    public function isAccessible()
    {
        if ($this->accessible === null) {
            try {
                $response = $this->client()->get('/');
                $body     = $response->getBody();
                if ($body->getSize() < 1024 && $body->getContents() === 'OK') {
                    $this->accessible = true;
                    return true;
                }
            } catch (RequestException $e) {
                throw new JuvimgImageResizeFailedException('Failed to resize image', $e->getCode(), $e);
            }
            $this->accessible = false;
        }
        return $this->accessible;
    }

    /**
     * Configures the Guzzle client for juvimg service
     *
     * @return Client
     */
    private function client()
    {
        if (!$this->client) {
            $this->client = new Client(
                [
                    'base_uri' => $this->url,
                    'auth'     => ['user', $this->password],
                ]
            );
        }
        return $this->client;
    }

    /**
     * Configure resize url
     *
     * @param int    $width
     * @param int    $height
     * @param string $mode
     * @param int    $quality
     * @return string
     */
    private function getResizeUrl(
        int $width, int $height, string $mode = ImageInterface::THUMBNAIL_INSET, int $quality = 70
    )
    {
        return sprintf('/resized/%d/%d/%d/%s', $width, $height, $quality, $mode);
    }

}