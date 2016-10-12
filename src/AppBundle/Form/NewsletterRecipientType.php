<?php

namespace AppBundle\Form;

use AppBundle\Entity\EventRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsletterRecipientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add(
                'email', TextType::class,
                array(
                    'label' => 'E-Mail',
                    'attr'  => array('aria-describedby' => 'help-email'),
                )
            )
            ->add(
                'hasAgeRelevant',
                CheckboxType::class,
                array(
                    'label'    => 'Altersgerechte Aktionen',
                    'attr'     => array('aria-describedby' => 'help-topic-age'),
                    'required' => false,
                    'mapped'   => true
                )
            )
            ->add(
                'topicChild',
                CheckboxType::class,
                array(
                    'label'    => 'Aktionen für Kinder',
                    'attr'     => array('aria-describedby' => 'help-topic-child'),
                    'required' => false,
                    'mapped'   => true
                )
            )
            ->add(
                'topicTeen',
                CheckboxType::class,
                array(
                    'label'    => 'Aktionen für Jugendliche',
                    'attr'     => array('aria-describedby' => 'help-topic-teen'),
                    'required' => false,
                    'mapped'   => true
                )
            )
            ->add(
                'subscribedEvents',
                EntityType::class,
                array(
                    'label'         => 'Ähnliche Veranstaltungen',
                    'attr'          => array('aria-describedby' => 'help-topic-subscribed'),
                    'class'         => 'AppBundle:Event',
                    'query_builder' => function (EventRepository $er) {
                        return $er->createQueryBuilder('e')
                                  ->orderBy('e.title', 'ASC');
                        //->where('e.deleted_at IS NULL');
                    },
                    'choice_label'  => 'title',
                    'multiple'      => true,
                    // 'expanded' => true,
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'AppBundle\Entity\NewsletterRecipient',
            )
        );
    }
}
