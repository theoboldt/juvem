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
use AppBundle\Entity\EventUserAssignment;
use AppBundle\Entity\User;
use AppBundle\Export\Customized\Configuration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventParticipantGroupByType extends AbstractType
{
    const EVENT_FIELD = 'event';
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $configuration  = new Configuration($options[self::EVENT_FIELD]);
        $tree           = $configuration->getConfigTreeBuilder()->buildTree();
        $children       = $tree->getChildren();
        $participant    = $children['participant']->getChildren();
        $grouingSorting = $participant['grouping_sorting']->getChildren();
        $grouing        = $grouingSorting['grouping']->getChildren();
        $grouingField   = $grouing['field'];
        $choices        = $grouingField->getValues();
        
        $builder
            ->add(
                'groupByField',
                ChoiceType::class,
                [
                    'label'    => 'Gruppieren nach',
                    'choices'  => $choices,
                    'expanded' => false,
                    'multiple' => false,
                    'required' => false
                ]
            );
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::EVENT_FIELD);
        $resolver->setAllowedTypes(self::EVENT_FIELD, Event::class);
    }
}

