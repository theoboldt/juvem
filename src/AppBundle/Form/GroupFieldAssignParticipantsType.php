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

use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Entity\AcquisitionAttribute\GroupFilloutValue;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\HumanTrait;
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

class GroupFieldAssignParticipantsType extends AbstractType
{
    /**
     * EM
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * GroupFieldAssignParticipantsType constructor.
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
        $eid   = $event->getEid();

        /** @var AttributeChoiceOption $currentGroupOption */
        $currentGroupOption = $options['choiceOption'];
        $optionId           = $currentGroupOption->getId();
        $attribute          = $currentGroupOption->getAttribute();

        $entityType = $options['entities'];


        $builder
            ->add(
                'assign',
                ChoiceType::class,
                [
                    'label'         => 'Neu hinzufügen',
                    'choice_loader' => new CallbackChoiceLoader(
                        function () use (
                            $event,
                            $currentGroupOption,
                            $entityType,
                            $attribute
                        ) {
                            $qbf = $this->em->createQueryBuilder();
                            $qbf->select('f')
                                ->from(Fillout::class, 'f')
                                ->andWhere($qbf->expr()->eq('f.attribute', $attribute->getBid()));

                            $qbe = $this->em->createQueryBuilder();
                            $qbe->select(['i.nameFirst', 'i.nameLast'])
                                ->from($entityType, 'i');
                            switch ($entityType) {
                                case Participant::class:
                                    $qbe->indexBy('i', 'i.aid');
                                    $qbe->addSelect(['i.aid', 'i.birthday']);
                                    $qbe->innerJoin('i.participation', 'p', Join::WITH);
                                    $qbe->andWhere($qbe->expr()->eq('p.event', ':eid'));
                                    $qbf->andWhere($qbf->expr()->in('f.participant', ':ids'));
                                    break;
                                case Participation::class:
                                    $qbe->indexBy('i', 'i.pid');
                                    $qbe->addSelect('i.pid');
                                    $qbe->andWhere($qbe->expr()->eq('i.event', ':eid'));

                                    $qbf->andWhere($qbf->expr()->in('f.participation', ':ids'));
                                    break;
                                case Employee::class:
                                    $qbe->indexBy('i', 'i.gid');
                                    $qbe->addSelect('i.gid');
                                    $qbe->andWhere($qbe->expr()->eq('i.event', ':eid'));

                                    $qbf->andWhere($qbf->expr()->in('f.employee', ':ids'));
                                    break;
                                default:
                                    throw new InvalidArgumentException('Unknown entity class transmitted');
                            }
                            $qbe->setParameter('eid', $event->getEid());

                            $entities = $qbe->getQuery()->execute();

                            if (!count($entities)) {
                                return [];
                            }

                            $qbf->setParameter('ids', array_keys($entities));
                            $fillouts = $qbf->getQuery()->execute();

                            /** @var Fillout $fillout */
                            foreach ($fillouts as $fillout) {
                                /** @var GroupFilloutValue $value */
                                $value = $fillout->getValue();
                                switch ($entityType) {
                                    case Participant::class:
                                        $id = $fillout->getParticipant()->getId();
                                        break;
                                    case Participation::class:
                                        $id = $fillout->getParticipation()->getId();
                                        break;
                                    case Employee::class:
                                        $id = $fillout->getEmployee()->getId();
                                        break;
                                    default:
                                        throw new InvalidArgumentException('Unknown entity class transmitted');
                                }
                                if ($value->getGroupId() === $currentGroupOption->getId()) {
                                    if (isset($entities[$id])) {
                                        unset($entities[$id]);
                                    }
                                } else {
                                    $option = $attribute->getChoiceOption($value->getGroupId());
                                    if (!$option) {
                                        continue;
                                    }
                                    if (!isset($entities[$id]['groups'])) {
                                        $entities[$id]['groups'] = [];
                                    }
                                    $entities[$id]['groups'][] = $option->getManagementTitle(true);

                                }
                            }

                            $choiceOptions = [];
                            foreach ($entities as $id => $entity) {
                                $title = HumanTrait::generateFullname($entity['nameLast'], $entity['nameFirst']);
                                if (isset($entity['birthday'])) {
                                    $title .= ' (' . EventRepository::yearsOfLife(
                                            $entity['birthday'], $event->getStartDate()
                                        ) . ')';
                                }
                                if (isset($entity['groups']) && count($entity['groups'])) {
                                    $title .= ' [' . implode(', ', $entity['groups']) . ']';
                                }
                                $title                 = htmlspecialchars($title, ENT_NOQUOTES);
                                $choiceOptions[$title] = $id;
                            }

                            return $choiceOptions;
                        }
                    ),
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
                            if (!$entityIds) {
                                return [];
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
