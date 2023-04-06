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


use AppBundle\Entity\Event;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventEntityType extends AbstractType implements DataTransformerInterface
{
    
    const INCLUDE_DELETED = 'include_deleted';
    
    const EXCLUDE_EVENT_EID = 'exclude_event_eid';
    
    /**
     * EM
     *
     * @var EntityManagerInterface
     */
    private $em;
    
    /**
     * AirlineChoiceType constructor.
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
        $builder->addModelTransformer($this);
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                self::INCLUDE_DELETED       => false,
                self::EXCLUDE_EVENT_EID     => null,
                'choice_loader'             => function (Options $options) {
                    $includeDeleted = $options[self::INCLUDE_DELETED];
                    $excludeEid     = $options[self::EXCLUDE_EVENT_EID];
                    
                    return new CallbackChoiceLoader(
                        function () use ($includeDeleted, $excludeEid) {
                            $qb = $this->em->createQueryBuilder();
                            if (!$includeDeleted) {
                                $qb->andWhere('e.deletedAt IS NULL');
                            }
                            if ($excludeEid) {
                                $qb->andWhere('e.eid <> ' . (int)$excludeEid);
                            }
                            
                            $qb->select(['e.title', 'e.startDate'])
                               ->from(Event::class, 'e', 'e.eid')
                               ->addOrderBy('e.startDate', 'DESC')
                               ->addOrderBy('e.startTime', 'DESC')
                               ->addOrderBy('e.title');
                            
                            $qbResult = $qb->getQuery()->execute();
                            $titles = [];
                            foreach ($qbResult as $eventData) {
                                if (isset($titles[$eventData['title']])) {
                                    ++$titles[$eventData['title']];
                                } else {
                                    $titles[$eventData['title']] = 1;
                                }
                            }
                            
                            $result = [];
                            foreach ($qbResult as $eid => $eventData) {
                                $eventTitle = $eventData['title'];
                                $year       = $eventData['startDate']->format('Y');
                                
                                
                                if (strpos($eventTitle, $year) !== false) {
                                    $date = $eventData['startDate']->format(Event::DATE_FORMAT_DATE);
                                } else {
                                    $date = $year;
                                }
                                
                                if ($titles[$eventTitle] > 1) {
                                    $eventTitle .= ' [' . $date . ']';
                                }
                                $result[$eventTitle] = $eid;
                            }
                            
                            return $result;
                        }
                    );
                },
                'choice_translation_domain' => false,
                'multiple'                  => false,
            ]
        );
        
        $resolver->setAllowedTypes(self::INCLUDE_DELETED, 'bool');
        $resolver->setAllowedTypes(self::EXCLUDE_EVENT_EID, ['int', 'null']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
    
    /**
     * {@inheritdoc}
     */
    public function transform($event)
    {
        if ($event instanceof Collection) {
            $ids = [];
            /** @var Event $item */
            foreach ($event as $item) {
                $ids[] = $item->getEid();
            }
            
            return $ids;
        } else {
            /** @var Event|null $event */
            if ($event === null) {
                return '';
            }
            
            return $event->getEid();
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function reverseTransform($eventId)
    {
        if (is_array($eventId)) {
            $qb = $this->em->createQueryBuilder();
            $qb->select('e')
               ->from(Event::class, 'e')
               ->andWhere('e.id IN (:eids)');
            $result = $qb->getQuery()->execute(['eids' => $eventId]);
            
            return $result;
        } else {
            if (!$eventId) {
                return null;
            }
            
            $qb = $this->em->createQueryBuilder();
            $qb->select('e')
               ->from(Event::class, 'e')
               ->andWhere('e.eid = :eid');
            $result = $qb->getQuery()->execute(['eid' => $eventId]);
            
            return count($result) ? $result[0] : null;
        }
    }
    
}
