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


use AppBundle\Entity\Audit\CreatedTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;


/**
 * @Vich\Uploadable
 * @ORM\Entity
 * @ORM\Table(name="user_attachment")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\UserAttachmentRepository")
 */
class UserAttachment implements ProvidesCreatedInterface
{
    use CreatedTrait;

    /**
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id = null;
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="attachments", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="uid")
     * @var User
     */
    protected $user;

    /**
     * @Vich\UploadableField(mapping="user_attachment", fileNameProperty="filename")
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
     * @ORM\Column(type="string", length=255, name="filename_original", nullable=false)
     *
     * @var string|null
     */
    private $filenameOriginal = null;


    /**
     * UserAttachment constructor.
     *
     * @param User|null $user
     * @param File|null $file
     */
    public function __construct(User $user = null, File $file = null)
    {
        $this->createdAt = new \DateTime();
        $this->user      = $user;
        if ($file) {
            $this->setFile($file);
        }
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
     * @return void
     */
    public function setFile(File $file): void
    {
        $this->file = $file;

        if ($file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->modifiedAt = new \DateTime('now');
        }
    }

    /**
     * @return null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param null $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @param string|null $filename
     */
    public function setFilename(?string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * @return string|null
     */
    public function getFilenameOriginal(): ?string
    {
        return $this->filenameOriginal;
    }

    /**
     * @param string|null $filenameOriginal
     */
    public function setFilenameOriginal(?string $filenameOriginal): void
    {
        $this->filenameOriginal = $filenameOriginal;
    }


}
