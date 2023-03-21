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

use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\CustomField\CustomFieldValueCollection;
use AppBundle\Entity\CustomField\GroupCustomFieldValue;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use InvalidArgumentException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupFieldAssignEntitiesType extends AbstractType
{
    /**
     * EM
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * GroupFieldAssignEntitiesType constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Event $event */
        $event = $options['event'];

        /** @var AttributeChoiceOption $currentGroupOption */
        $currentGroupOption = $options['choiceOption'];
        $attribute          = $currentGroupOption->getAttribute();

        $entityType = $options['entities'];
        
        $builder
            ->add(
                'assign',
                ChoiceType::class,
                [
                    'label'         => $currentGroupOption->getManagementTitle(true),
                    'choice_loader' => new CallbackChoiceLoader(
                        function () use (
                            $event,
                            $currentGroupOption,
                            $entityType,
                            $attribute
                        ) {
                            $qbe = $this->em->createQueryBuilder();
                            $qbe->select(['i.nameFirst', 'i.nameLast', 'i.customFieldValues'])
                                ->from($entityType, 'i')
                                ->andWhere('i.deletedAt IS NULL')
                                ->addOrderBy('i.nameLast')
                                ->addOrderBy('i.nameFirst');
                            switch ($entityType) {
                                case Participant::class:
                                    $qbe->indexBy('i', 'i.aid');
                                    $qbe->addSelect(['i.aid', 'i.birthday', 'i.status']);
                                    $qbe->innerJoin('i.participation', 'p', Join::WITH);
                                    $qbe->andWhere($qbe->expr()->eq('p.event', ':eid'));
                                    break;
                                case Participation::class:
                                    $qbe->indexBy('i', 'i.pid');
                                    $qbe->addSelect('i.pid');
                                    $qbe->andWhere($qbe->expr()->eq('i.event', ':eid'));
                                    break;
                                case Employee::class:
                                    $qbe->indexBy('i', 'i.gid');
                                    $qbe->addSelect('i.gid');
                                    $qbe->andWhere($qbe->expr()->eq('i.event', ':eid'));
                                    break;
                                default:
                                    throw new InvalidArgumentException('Unknown entity class transmitted');
                            }
                            $qbe->setParameter('eid', $event->getEid());

                            $entities = $qbe->getQuery()->execute();

                            foreach ($entities as $id => $entity) {
                                $entities[$id]['groups'] = [];
                                if (is_array($entity['customFieldValues'])) {
                                    $customFieldValueCollection = CustomFieldValueCollection::createFromArray(
                                        $entity['customFieldValues']
                                    );
                                    $customFieldValueContainer = $customFieldValueCollection->get($attribute->getBid());
                                    if ($customFieldValueContainer) {
                                        $customFieldValue = $customFieldValueContainer->getValue();
                                        if ($customFieldValue instanceof GroupCustomFieldValue) {
                                            $customFieldValueGroupId = $customFieldValue->getValue();
                                            
                                            if ($customFieldValueGroupId === $currentGroupOption->getId()) {
                                                //already part of this group
                                                unset($entities[$id]);
                                            } elseif ($customFieldValueGroupId) {
                                                $customFieldChoiceOption =$attribute->getChoiceOption($customFieldValueGroupId); 
                                                if ($customFieldChoiceOption) {
                                                    $entities[$id]['groups'][] = $customFieldChoiceOption->getManagementTitle(true);
                                                } else {
                                                    $entities[$id]['groups'][] = $customFieldValueGroupId;
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $choiceOptions = [];
                            foreach ($entities as $id => $entity) {
                                $choiceOption = new GroupFieldAssignEntityChoiceOption(
                                    $event,
                                    $id,
                                    $entity['nameFirst'],
                                    $entity['nameLast'],
                                    isset($entity['groups']) ? $entity['groups'] : [],
                                    isset($entity['birthday']) ? $entity['birthday'] : null,
                                    isset($entity['status']) ? new ParticipantStatus($entity['status']) : null
                                );
        
                                if ($choiceOption->getStatus() !== null
                                    && ($choiceOption->getStatus()->has(ParticipantStatus::TYPE_STATUS_REJECTED)
                                        || $choiceOption->getStatus()->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN))) {
                                    continue;
                                }
        
                                $choiceOptions[] = $choiceOption;
                            }
    
                            return $choiceOptions;
                        }
                    ),
                    'choice_attr' => function (GroupFieldAssignEntityChoiceOption $entity, $key, $value) {
                        $classes = [];
    
                        $status = $entity->getStatus();
                        if ($status) {
                            foreach ($status->getActiveList(false) as $state) {
                                $classes[] = 'has-status-' . $state;
                            }
                        }
    
                        if ($entity->hasGroups()) {
                            $classes[] = 'has-groups';
                        }
                        if (count($classes)) {
                            return ['class' => implode(' ', $classes)];
                        } else {
                            return [];
        
                        }
                    },
                    'choice_value'  => function (GroupFieldAssignEntityChoiceOption $entity = null) {
                        return $entity ? $entity->getId() : '';
                    },
                    'choice_label'  => function (GroupFieldAssignEntityChoiceOption $entity = null) {
                        return $entity ? (string)$entity : '';
                    },
                    'multiple'      => true,
                    'required'      => false,
                ]
            );

        $builder->get('assign')
                ->addModelTransformer(
                    new CallbackTransformer(
                        function ($entities) {
                            if (!$entities) {
                                return [];
                            }
                            $result = [];
                            foreach ($entities as $entity) {
                                $result[] = $entity->getId();
                            }
                            return $result;
                        },
                        function ($entityIds) use ($entityType) {
                            if (!$entityIds || !count($entityIds)) {
                                return [];
                            }
                            $firstEntityId = reset($entityIds);
                            if ($firstEntityId instanceof GroupFieldAssignEntityChoiceOption) {
                                $entityId = null;
                                /** @var GroupFieldAssignEntityChoiceOption $entityId */
                                foreach ($entityIds as &$entityId) {
                                    $entityId = $entityId->getId();
                                }
                                unset($entityId);
                            }
                            
                            $qb = $this->em->createQueryBuilder();
                            $qb->select(['e']);
                            switch ($entityType) {
                                case Participant::class:
                                    $qb->from(Participant::class, 'e')
                                       ->where($qb->expr()->in('e.aid', ':idlist'));
                                    break;
                                case Participation::class:
                                    $qb->from(Participation::class, 'e')
                                       ->where($qb->expr()->in('e.pid', ':idlist'));
                                    break;
                                case Employee::class:
                                    $qb->from(Employee::class, 'e')
                                       ->where($qb->expr()->in('e.gid', ':idlist'));
                                    break;
                                default:
                                    throw new InvalidArgumentException('Unknown entity class transmitted');
                            }
                            $qb->setParameter('idlist', $entityIds);
                            $result = $qb->getQuery()->execute();

                            return $result;
                        }
                    )
                );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('event');
        $resolver->setAllowedTypes('event', Event::class);

        $resolver->setRequired('choiceOption');
        $resolver->setAllowedTypes('choiceOption', AttributeChoiceOption::class);

        $resolver->setRequired('entities');
        $resolver->setAllowedTypes('entities', 'string');
    }
}
