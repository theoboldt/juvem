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
use AppBundle\Entity\NewsletterSubscription;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsletterSubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add(
                'email',
                TextType::class,
                array(
                    'label' => 'E-Mail',
                    'attr'  => array('aria-describedby' => 'help-email'),
                )
            )
            ->add(
                'isEnabled',
                CheckboxType::class,
                array(
                    'label'      => 'Newsletter erhalten',
                    'attr'       => array('aria-describedby' => 'help-newsletter-enable', 'class' => 'checkbox-smart'),
                    'label_attr' => array('class' => 'control-label checkbox-smart-label'),
                    'required'   => false,
                    'mapped'     => true
                )
            )
            ->add(
                'nameLast',
                TextType::class,
                array(
                    'label'    => 'Familienname',
                    'required' => false,
                    'attr'     => array('aria-describedby' => 'help-last-name')
                )
            )
            ->add(
                'useAging',
                CheckboxType::class,
                array(
                    'label'    => 'Abbonierte Altersspanne wächst mit',
                    'attr'     => array('aria-describedby' => 'help-topic-ageing'),
                    'required' => false,
                    'mapped'   => true
                )
            )
            ->add(
                'ageRangeBegin',
                NumberType::class,
                array(
                    'label'    => 'Altersspanne (minimales Alter)',
                    'attr'     => array('aria-describedby' => 'help-topic-range'),
                    'required' => false,
                    'mapped'   => true
                )
            )
            ->add(
                'ageRangeEnd',
                NumberType::class,
                array(
                    'label'    => 'Altersspanne (maximales Alter)',
                    'attr'     => array('aria-describedby' => 'help-topic-range'),
                    'required' => false,
                    'mapped'   => true
                )
            )
            ->add(
                'events',
                EntityType::class,
                array(
                    'label'         => 'Ähnliche Veranstaltungen',
                    'attr'          => array('aria-describedby' => 'help-topic-subscribed'),
                    'class'         => 'AppBundle:Event',
                    'query_builder' => function (EventRepository $er) {
                        return $er->createQueryBuilder('e')
                                  ->where('e.deletedAt IS NULL')
                                  ->orderBy('e.title', 'ASC');
                    },
                    'choice_label'  => 'title',
                    'multiple'      => true,
                    'required'      => false
                    // 'expanded' => true,
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => NewsletterSubscription::class,
            )
        );
    }
}
