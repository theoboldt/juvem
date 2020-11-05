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

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ ORM \Entity
 * @ORM\Table(name="upload_file")
 */
class UploadFile
{
    /**
     * @ORM\Column(type="integer", name="fid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $fid;

    /**
     * @ORM\Column(type="string", name="file_name")
     * @Assert\NotBlank()
     */
    protected $fileName;

    /**
     * @ORM\Column(type="string", length=160, options={"fixed" = true}, name="checksum")
     * @Assert\NotBlank()
     */
    protected $checksum;

    /**
     * @ORM\Column(type="string", name="mime_type")
     * @Assert\NotBlank()
     */
    protected $mimeType;

    /**
     * @ORM\Column(type="string", length=255, name="description")
     */
    protected $description = '';


    /**
     * Get fid
     *
     * @return integer
     */
    public function getFid()
    {
        return $this->fid;
    }

    /**
     * Set fileName
     *
     * @param string $fileName
     *
     * @return UploadFile
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set checksum
     *
     * @param string $checksum
     *
     * @return UploadFile
     */
    public function setChecksum($checksum)
    {
        $this->checksum = $checksum;

        return $this;
    }

    /**
     * Get checksum
     *
     * @return string
     */
    public function getChecksum()
    {
        return $this->checksum;
    }

    /**
     * Set mimeType
     *
     * @param string $mimeType
     *
     * @return UploadFile
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get mimeType
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return UploadFile
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
