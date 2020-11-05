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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class JuvimgService
{
    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

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
     * Cached HTTP client
     *
     * @var Client
     */
    private $client;

    /**
     * Create instance if configuration is valid
     *
     * @param string|null    $url      URL to juvimg installation
     * @param string|null    $password Password configured in juvimg for Basic authentication
     * @param LoggerInterface $logger   Logger
     * @return JuvimgService|null      Service or null if not valid
     */
    public static function create(string $url = null, string $password = null, LoggerInterface $logger = null)
    {
        if (!empty($url) && !empty($password)) {
            return new self($url, $password, $logger);
        } else {
            return null;
        }
    }

    /**
     * JuvimgService constructor.
     *
     * @param string              $url      URL to juvimg installation
     * @param string              $password Password configured in juvimg for Basic authentication
     * @param LoggerInterface|null $logger   Logger
     */
    public function __construct(string $url, string $password, LoggerInterface $logger = null)
    {
        $this->url      = rtrim($url, '/');
        $this->password = $password;
        $this->logger   = $logger ?: new NullLogger();
    }

    /**
     * Resize an image using juvimg
     *
     * @param  string  $path    Path to image file on local filesystem
     * @param  integer $width   Width of image
     * @param  integer $height  Height of image
     * @param  string  $mode    Either ImageInterface::THUMBNAIL_INSET or ImageInterface::THUMBNAIL_OUTBOUND
     * @param  int     $quality JPG image quality applied when resizing
     * @return StreamInterface
     */
    public function resize(
        string $path, int $width, int $height, string $mode = ImageInterface::THUMBNAIL_INSET, int $quality = 70
    )
    {
        if ($this->accessible === false) {
            $this->logger->warning('Resize requested but juvimg is unaccessible');
            throw new JuvimgUnaccessibleException('Can not resize image because juvimg is unaccessible');
        }
    
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($path);
    
        $url = null;
        try {
            $this->logger->debug('Requested resize of "' . $path . '"');
            $url   = $this->getResizeUrl($width, $height, $mode, $quality);
            $result = $this->client()->post(
                $this->getResizeUrl($width, $height, $mode, $quality),
                ['body' => fopen($path, 'r'), 'content-type' => $mimeType, 'Accept' => $mimeType]
            );
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $body = $e->getResponse()->getBody();
                if ($body->getSize() < 1024) {
                    $this->logger->warning(
                        'Failed to resize image: {body}',
                        ['url' => $url, 'body' => $body->getContents()]
                    );
                } else {
                    $this->logger->warning('Failed to resize image {url}', ['url' => $url]);
                }
            }
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
                    $this->logger->notice('Juvimg service is accessible');
                    $this->accessible = true;
                    return true;
                }
            } catch (RequestException $e) {
                $this->logger->warning('Juvimg service is not accessible, request failed');
            }
            $this->accessible = false;
            $this->logger->notice('Juvimg service does not seem to be accessible');
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
     * @param  integer $width   Width of image
     * @param  integer $height  Height of image
     * @param  string  $mode    Either ImageInterface::THUMBNAIL_INSET or ImageInterface::THUMBNAIL_OUTBOUND
     * @param  int     $quality JPG image quality applied when resizing
     * @return string
     */
    private function getResizeUrl(
        int $width, int $height, string $mode = ImageInterface::THUMBNAIL_INSET, int $quality = 70
    )
    {
        return sprintf('/index.php/resized/%d/%d/%d/%s', $width, $height, $quality, $mode);
    }

}