<?php

namespace AppBundle\Manager;

use AppBundle\Entity\Event;
use \AppBundle\Entity\Participation as ParticipationEntity;
use AppBundle\Entity\Participant;
use AppBundle\UploadImage;
use Doctrine\Common\Cache\FilesystemCache;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Swift_Mailer;
use Symfony\Component\Templating\EngineInterface;

class UploadImageManager
{
    /**
     * Contains the cache instance
     *
     * @var FilesystemCache|null
     */
    protected $cache = null;

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
     * Create new instance of upload image manager
     *
     * @param string $cacheDir Cache dir where modified images reside
     * @param array $uploadMappings Available upload mappings
     */
    public function __construct($cacheDir, array $uploadMappings)
    {
        $this->cache = new FilesystemCache($cacheDir);

        if (!array_key_exists('event_image', $uploadMappings)) {
            throw new \InvalidArgumentException('Could not find any upload mapping in vich upload configuration');
        }
        $this->uploadMapping = 'event_image';
        $this->uploadsDir = $uploadMappings['event_image']['upload_destination'];
    }

    public function fetch($name, $width = null, $height = null)
    {
        if ($width === null && $height === null) {
            return new UploadImage(
                $this->getOriginalImagePath($name));
        } else {
            $this->fetchResized($name, $width, $height);
        }
    }

    /**
     * Fetch a resized image
     *
     * @param   string $name Image name
     * @param   integer $width Width of image
     * @param   integer $height Height of image
     * @return UploadImage
     */
    public function fetchResized($name, $width, $height)
    {
        $key = $this->key($name, $width, $height);
        if (!$this->cache->contains($key)) {
            $imagine = new Imagine();
            $size = new Box($width, $height);
            $mode = ImageInterface::THUMBNAIL_INSET;
            $image = $imagine->open($this->getOriginalImagePath($name))
                             ->thumbnail($size, $mode)
                             ->get($this->getOriginalImageType($name));
            $this->cache->save($key, $image);
        }

        return new UploadImage($this->getOriginalImagePath($name), $this->cache->fetch($key));
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
         * @param bool $asMimeType Set to true if the mime type should be returned and not jpg or png
         * @return string               Image type as mime type or png or jpg
         */
        public function getOriginalImageType($name, $asMimeType = false)
        {
            $path = $this->getOriginalImagePath($name);
            $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);

            if ($asMimeType) {
                return $mimeType;
            }

            return ($mimeType == 'image/jpeg') ? 'jpg' : 'png';
        }

    public function key($name, $width, $height)
    {
        return sprintf('%s_%s_%s_%s', $this->uploadMapping, $name, $width, $height);
    }
}