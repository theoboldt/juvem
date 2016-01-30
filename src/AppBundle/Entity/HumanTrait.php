<?php
namespace AppBundle\Entity;


trait HumanTrait
{

    /**
     * @ORM\Column(type="string", length=128, name="name_first")
     */
    protected $nameFirst;

    /**
     * @ORM\Column(type="string", length=128, name="name_last")
     * @Assert\NotBlank()
     */
    protected $nameLast;

    /**
     * Set nameFirst
     *
     * @param string $nameFirst
     *
     * @return self
     */
    public function setNameFirst($nameFirst)
    {
        $this->nameFirst = $nameFirst;

        return $this;
    }

    /**
     * Get nameFirst
     *
     * @return string
     */
    public function getNameFirst()
    {
        return $this->nameFirst;
    }

    /**
     * Set nameLast
     *
     * @param string $nameLast
     *
     * @return self
     */
    public function setNameLast($nameLast)
    {
        $this->nameLast = $nameLast;

        return $this;
    }

    /**
     * Get nameLast
     *
     * @return string
     */
    public function getNameLast()
    {
        return $this->nameLast;
    }
}