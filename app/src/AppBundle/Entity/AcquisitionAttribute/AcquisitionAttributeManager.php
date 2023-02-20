<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\AcquisitionAttribute;

use Doctrine\Bundle\DoctrineBundle\Registry;

class AcquisitionAttributeManager
{
    /**
     * Database abstraction
     *
     * @var Registry
     */
    private Registry $doctrine;

    /**
     * Comment repository
     *
     * @var AcquisitionAttributeRepository
     */
    private AcquisitionAttributeRepository $repository;

    /**
     * @var Attribute[]|null
     */
    private ?array $attributesCache = null;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine   = $doctrine;
        $this->repository = $this->doctrine->getRepository(Attribute::class);
    }

    /**
     * Clear {@see Attribute} cache
     *
     * @return void
     */
    private function clearCache(): void
    {
        $this->attributesCache = null;
    }

    /**
     * @return array|Attribute[]
     */
    public function getAttributes(): array
    {
        if ($this->attributesCache === null) {
            $this->attributesCache = [];
            /** @var Attribute $attribute */
            foreach ($this->repository->findAllWithOptions() as $attribute) {
                $this->attributesCache[$attribute->getBid()] = $attribute;
            }
        }
        return $this->attributesCache;
    }

    /**
     * Persist and flush attribute
     * 
     * @param Attribute $attribute
     * @return void
     */
    public function persistAndFlush(Attribute $attribute): void
    {
        $em = $this->doctrine->getManager();
        $em->persist($attribute);
        $em->flush();
        $this->clearCache();
    }
}
