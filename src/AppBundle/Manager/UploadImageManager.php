<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager;

use AppBundle\Cache\FileCache;
use AppBundle\Cache\FileCachePathGeneratorResizedImage;
use AppBundle\Juvimg\JuvimgNoResizePerformedException;
use AppBundle\Juvimg\JuvimgService;
use AppBundle\UploadImage\ResizedUploadImage;
use AppBundle\UploadImage\UploadImage;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class UploadImageManager
{
    /**
     * Contains the cache instance
     *
     * @var FileCache
     */
    protected $cache;

    /**
     * Temporary dir for resized images
     *
     * @var string
     */
    private $tmpDir;

    /**
     * Contains the path where upload images are stored
     *
     * @var string
     */
    protected $uploadsDir;

    /**
     * Contains the name of the used upload mapping
     *
     * @var string
     */
    protected $uploadMapping;
    
    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * JuvimgService if available
     *
     * @var JuvimgService|null
     */
    private $juvimgService;
    
    /**
     * Create new instance of upload image manager
     *
     * @param FileCache $cache
     * @param string $tmpDir
     * @param array $uploadMappings       Available upload mappings
     * @param string $mapping             Mapping to use
     * @param LoggerInterface|null $logger Logger
     * @param JuvimgService|null $juvimg  Juvimg Resize service if available
     */
    public function __construct(
        FileCache $cache,
        string $tmpDir,
        array $uploadMappings,
        string $mapping,
        ?LoggerInterface $logger = null,
        ?JuvimgService $juvimg = null
    )
    {
        
        if (!array_key_exists($mapping, $uploadMappings)) {
            throw new \InvalidArgumentException('Could not find desired upload mapping in vich upload configuration');
        }
        $this->cache         = $cache;
        $this->tmpDir        = $tmpDir;
        $this->uploadMapping = $mapping;
        $this->uploadsDir    = $uploadMappings[$mapping]['upload_destination'];
        $this->logger        = $logger ?: new NullLogger();
        $this->juvimgService = $juvimg;
    }

    /**
     * Fetch image
     *
     * @param string   $name
     * @param null|int $width
     * @param null|int $height
     * @return UploadImage
     */
    public function fetch($name, $width = null, $height = null)
    {
        if ($width === null && $height === null) {
            return new UploadImage(
                $this->getOriginalImagePath($name)
            );
        } else {
            $this->fetchResized($name, $width, $height);
        }
    }

    /**
     * Fetch a resized image
     *
     * @param  string  $name    Image name
     * @param  integer $width   Width of image
     * @param  integer $height  Height of image
     * @param  string  $mode    Either ImageInterface::THUMBNAIL_INSET or ImageInterface::THUMBNAIL_OUTBOUND
     * @param  int     $quality JPG image quality applied when resizing
     * @return UploadImage
     */
    public function fetchResized($name, $width, $height, $mode = ImageInterface::THUMBNAIL_INSET, $quality = 70)
    {
        $tmpFile      = null;
        $key          = new FileCachePathGeneratorResizedImage($width, $height, $mode, $name);
        $originalPath = $this->getOriginalImagePath($name);
        if (!$this->cache->contains($key)) {
            $tmpFile = tempnam($this->tmpDir, 'imagine_resize');
            $image = null;
            if ($this->juvimgService && $this->juvimgService->isAccessible()) {
                $this->logger->info('Resizing image {name} using juvimg service', ['name' => $name]);
                $start = microtime(true);
                try {
                    $result = $this->juvimgService->resize(
                        $originalPath, $width, $height, $mode, $quality
                    );

                    $image = fopen($tmpFile, 'w');
                    // Read until the stream is closed
                    while (!$result->eof()) {
                        fwrite($image, $result->read(8192));
                    }
                    fclose($image);
                    $image = new \SplFileInfo($tmpFile);
                    $this->logger->info(
                        'Resized image {name} using juvimg service within {time} seconds',
                        ['name' => $name, 'time' => microtime(true) - $start]
                    );
                } catch (JuvimgNoResizePerformedException $e) {
                    $this->logger->warning(
                        'Failed to resize image {name} using juvimg service within {time} seconds, message: {message}',
                        ['name' => $name, 'time' => microtime(true) - $start, 'message' => $e->getMessage()]
                    );
                    $image = null;
                }
            }
            if (!$image) {
                $this->logger->debug('Resizing image {name} using Imagine', ['name' => $name]);
                $start   = microtime(true);
                $imagine = new Imagine();
                $size    = new Box($width, $height);
                $imagine->open($originalPath)
                        ->thumbnail($size, $mode)
                        ->save(
                            $tmpFile,
                            [
                                'jpeg_quality'          => $quality,
                                'png_compression_level' => 9,
                            ]
                        );
                $image = new \SplFileInfo($tmpFile);
                $this->logger->info(
                    'Resized image {name} using Imagine within {time} seconds',
                    ['name' => $name, 'time' => microtime(true) - $start]
                );
            }

            $this->cache->save($key, $image);
            if ($tmpFile) {
                unlink($tmpFile);
            }
        }

        return new ResizedUploadImage($this->cache->fetch($key)->getPathname(), $originalPath);
    }

    /**
     * Get path to original uploaded file
     *
     * @param $name
     * @return string
     */
    public function getOriginalImagePath($name)
    {
        return $this->uploadsDir . '/' . $name;
    }

    /**
     * Get the type of an image
     *
     * @param string $name
     * @param bool   $asMimeType Set to true if the mime type should be returned and not jpg or png
     * @return string               Image type as mime type or png or jpg
     */
    public function getOriginalImageType($name, $asMimeType = false)
    {
        $path     = $this->getOriginalImagePath($name);
        $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);

        if ($asMimeType) {
            return $mimeType;
        }

        return ($mimeType == 'image/jpeg') ? 'jpg' : 'png';
    }
}