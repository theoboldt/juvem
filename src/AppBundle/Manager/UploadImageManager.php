<?php

namespace AppBundle\Manager;

use AppBundle\Entity\Event;
use \AppBundle\Entity\Participation as ParticipationEntity;
use AppBundle\Entity\Participant;
use Doctrine\Common\Cache\FilesystemCache;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Swift_Mailer;
use Symfony\Component\Templating\EngineInterface;

class UploadImageManager
{
    const TYPE_PRESENTATION_THUMBNAIL = 1;

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

    public function __construct($cacheDir, array $uploadMappings) {
        $this->cache = new FilesystemCache($cacheDir);

        if (!array_key_exists('event_image', $uploadMappings)) {
            throw new \InvalidArgumentException('Could not find any upload mapping in vich upload configuration');
        }
        $this->uploadsDir = $uploadMappings['event_image']['upload_destination'];
    }

    public function fetchPresentationThumbnail($name) {
        $key = self::key($name, self::TYPE_PRESENTATION_THUMBNAIL);
        if (!$this->cache->contains($key)) {
            $imagine = new Imagine();
            $size    = new Box(445, 445);
            $mode    = ImageInterface::THUMBNAIL_INSET;
            $image = $imagine->open($this->uploadsDir.'/'.$name)
                ->thumbnail($size, $mode)
                ->get('jpg');
            ;
            $this->cache->save($key, $image);
            return $image;
        }
        return $this->cache->fetch($key);
    }

    public function fetch($name, $size) {

    }

    public static function key($name, $type) {
        return sprintf('%s_%s', $name, $type);
    }
}