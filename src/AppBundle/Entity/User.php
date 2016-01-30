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
    use HumanTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="uid")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @see getUid()
     * @return integer
     */
    public function getId()
    {
        return $this->getUid();
    }

    /**
     * @return integer
     */
    public function getUid()
    {
        return $this->id;
    }

    /**
     * Set email of this user
     *
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        $email = is_null($email) ? '' : $email;
        parent::setEmail($email);
        $this->setUsername($email);

        return $this;
    }
}
