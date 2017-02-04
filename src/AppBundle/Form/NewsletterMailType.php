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

use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Newsletter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsletterMailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject', TextType::class, array('label' => 'Betreff'))
            ->add('title', TextType::class, array('label' => 'Titel'))
            ->add('lead', TextType::class, array('label' => 'Untertitel', 'required' => false))
            ->add('content', TextareaType::class, array('label' => 'Hauptinhalt'))
            ->add(
                'ageRangeBegin',
                NumberType::class,
                array(
                    'label'    => 'Altersspanne (minimales Alter)',
                    'required' => false
                )
            )
            ->add(
                'ageRangeEnd',
                NumberType::class,
                array(
                    'label'    => 'Altersspanne (maximales Alter)',
                    'required' => false
                )
            )
            ->add(
                'events',
                EntityType::class,
                array(
                    'label'         => 'Ähnliche Veranstaltungen',
                    'class'         => 'AppBundle:Event',
                    'query_builder' => function (EventRepository $er) {
                        return $er->createQueryBuilder('e')
                                  ->where('e.deletedAt IS NULL')
                                  ->orderBy('e.title', 'ASC');
                    },
                    'choice_label'  => 'title',
                    'multiple'      => true,
                    'required'      => false
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Newsletter::class,
            )
        );
    }
}
