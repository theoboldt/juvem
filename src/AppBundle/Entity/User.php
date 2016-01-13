<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="`user`")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $uid;

    /**
     * @ORM\Column(type="string", length=128)
     */
    protected $nameFirst;

    /**
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    protected $nameLast;

}