<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Entity;

use AppBundle\Entity\Audit\CreatedModifiedTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;


/**
 * @Vich\Uploadable
 * @ORM\Entity
 * @ORM\Table(name="gallery_image")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\GalleryImageRepository")
 */
class GalleryImage
{
    use CreatedModifiedTrait;

    const THUMBNAIL_DIMENSION = 160;
    const PREVIEW_DIMENSION = 480;
    const THUMBNAIL_DETAIL = 800;

    /**
     * Gallery image id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    private $iid;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event", inversedBy="galleryImages")
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid")
     *
     * @var Event
     */
    private $event;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $title = '';

    /**
     * @Vich\UploadableField(mapping="gallery_image", fileNameProperty="filename")
     *
     * @var File
     */
    private $file;

    /**
     * @ORM\Column(type="string", length=255, name="filename", nullable=true)
     *
     * @var string|null
     */
    private $filename = null;

    /**
     * @ORM\Column(type="datetime", name="recorded_at", nullable=true)
     *
     * @var \DateTime
     */
    protected $recordedAt = null;
    
    /**
     * Image width
     *
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected $width = 0;
    
    /**
     * Image height
     *
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected $height = 0;

    /**
     * GalleryImage constructor.
     *
     * @param Event|null $event
     * @param File|null  $file
     */
    public function __construct(Event $event = null, File $file = null)
    {
        $this->modifiedAt = new \DateTime();
        $this->createdAt  = new \DateTime();
        $this->event      = $event;
        if ($file) {
            $this->setFile($file);
        }
    }

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * @param int $iid
     * @return GalleryImage
     */
    public function setIid($iid)
    {
        $this->iid = $iid;
        return $this;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     * @return GalleryImage
     */
    public function setEvent($event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return GalleryImage
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @return GalleryImage
     */
    public function setFile(File $file): GalleryImage
    {
        $this->file = $file;

        if ($file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->modifiedAt = new \DateTime('now');
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return GalleryImage
     */
    public function setFilename(string $filename = null): GalleryImage
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getRecordedAt()
    {
        return $this->recordedAt;
    }

    /**
     * @param \DateTimeInterface $recordedAt
     * @return GalleryImage
     */
    public function setRecordedAt(\DateTimeInterface $recordedAt = null)
    {
        $this->recordedAt = $recordedAt;
        return $this;
    }
    
    /**
     * Image width in pixels
     *
     * @return int
     */
    public function getWidth(): int {
        return $this->width;
    }
    
    /**
     * Store image width in pixels
     *
     * @param int $width
     * @return GalleryImage
     */
    public function setWidth($width) {
        $this->width = $width;
        return $this;
    }
    
    /**
     * Get image width in pixels
     *
     * @return int
     */
    public function getHeight(): int {
        return $this->height;
    }
    
    /**
     * Store image height in pixels
     *
     * @param int $height
     * @return GalleryImage
     */
    public function setHeight($height) {
        $this->height = $height;
        return $this;
    }
    
    /**
     * Get image aspect ratio depending on dimensions
     *
     * @return float|int
     */
    public function getAspectRatio() {
        $numerator = $this->getWidth();
        $divisor   = $this->getHeight();
        if (!$numerator || !$divisor) {
            return 1;
        }
        return $numerator/$divisor;
    }

}