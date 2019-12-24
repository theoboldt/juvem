<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\AcquisitionAttribute;


use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariable;
use AppBundle\Entity\AcquisitionAttribute\Variable\EventSpecificVariableValue;
use AppBundle\Entity\Event;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpecifyEventSpecificVariableValuesForEventType extends AbstractType
{
    const FIELD_EVENT = 'event';
    
    /**
     * em
     *
     * @var EntityManager
     */
    private $em;
    
    /**
     * MealFeedbackType constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em) { $this->em = $em; }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $event = $options[self::FIELD_EVENT];
        
        $variables = $this->em->getRepository(EventSpecificVariable::class)->findAllNotDeleted();
        
        /** @var EventSpecificVariable $variable */
        foreach ($variables as $variable) {
            $data = null;
            $eid  = $event->getEid();
            $vid  = $variable->getId();
            
            $values = $variable->getValues();
            /** @var EventSpecificVariableValue $value */
            foreach ($values as $value) {
                if ($value->getEvent()->getEid() === $eid
                    && $value->getVariable()->getId() === $vid
                ) {
                    $data = $value;
                    break;
                }
            }
            
            $builder
                ->add(
                    'variable_' . $variable->getId(),
                    EventSpecificVariableValueType::class,
                    [
                        'data'                                         => $data,
                        'label'                                        => $variable->getDescription(),
                        'required'                                     => !$variable->hasDefaultValue(),
                        'mapped'                                       => false,
                        EventSpecificVariableValueType::FIELD_VARIABLE => $variable,
                        EventSpecificVariableValueType::FIELD_EVENT    => $event,
                    ]
                );
        }
    }
    
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::FIELD_EVENT);
        $resolver->setAllowedTypes(self::FIELD_EVENT, Event::class);
    }
    
    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        
        $view->vars = array_merge(
            $view->vars,
            [
                self::FIELD_EVENT => $options[self::FIELD_EVENT]
            ]
        );
    }
}