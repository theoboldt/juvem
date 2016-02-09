<?php
namespace AppBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
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

}
