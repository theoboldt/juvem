<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form;

use AppBundle\Entity\User;
use AppBundle\Entity\UserAttachment;
use AppBundle\Entity\UserAttachmentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserAttachmentChoiceType extends EntityType
{

    /**
     * @var UserAttachmentRepository
     */
    private UserAttachmentRepository $repository;

    /**
     * security.token_storage
     *
     * @var TokenStorageInterface|null
     */
    private ?TokenStorageInterface $tokenStorage;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry, ?TokenStorageInterface $tokenStorage = null)
    {
        parent::__construct($registry);
        $this->repository   = $this->registry->getRepository(UserAttachment::class);
        $this->tokenStorage = $tokenStorage;
    }


    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['class']        = UserAttachment::class;
        $options['choice_label'] = 'filenameOriginal';
        $options['expanded']     = true;
        $options['multiple']     = true;
        $options['required']     = false;

        parent::buildForm($builder, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('class', UserAttachment::class);
        $resolver->setDefault('choice_label', 'filenameOriginal');
        $resolver->setDefault('label', 'Dateianhänge');
        $resolver->setDefault('expanded', true);
        $resolver->setDefault('multiple', true);
        $resolver->setDefault('required', false);

        $resolver->setDefault('query_builder', function (UserAttachmentRepository $r) {
            $user    = $this->getUser();
            $builder = $r->createQueryBuilder('e');
            if ($user) {
                $builder->andWhere($builder->expr()->eq('e.user', ':userId'));
                $builder->setParameter('userId', $user->getId());
            } else {
                $builder->andWhere($builder->expr()->eq('e.user', 0));
            }
            $builder->addOrderBy('e.filenameOriginal', 'ASC')
                    ->addOrderBy('e.createdAt', 'ASC');

            return $builder;
        });
    }

    /**
     * Get a user from the Security Token Storage.
     *
     * @return User|null
     */
    protected function getUser(): ?User
    {
        if (!$this->tokenStorage || null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!\is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        if (!$user instanceof User) {
            throw new \InvalidArgumentException('Unknown user class: ' . get_class($user));
        }

        return $user;
    }
}
