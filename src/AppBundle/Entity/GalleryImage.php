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

    const THUMBNAIL_DIMENSION = 150;

    /**
     * Gallery image id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iid;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event", inversedBy="galleryImages", cascade={"all"})
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="cascade")
     */
    private $event;

    /**
     * @ORM\Column(type="string", length=255)
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
     * @var string
     */
    private $filename;

    /**
     * @ORM\Column(type="datetime", name="recorded_at", nullable=true)
     * @var \DateTime
     */
    protected $recordedAt = null;

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
     * @return mixed
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * @param mixed $iid
     * @return GalleryImage
     */
    public function setIid($iid)
    {
        $this->iid = $iid;
        return $this;
    }

    /**
     * @return mixed
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
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
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
    public function getFile(): File
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
    public function setFilename(string $filename): GalleryImage
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return \DateTime
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

}