<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\Feedback;

use AppBundle\Feedback\FeedbackQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'topic',
                TextType::class,
                [
                    'label'    => 'Thema (Intern)',
                    'required' => false,
                ]
            )
            ->add(
                'thesis',
                TextareaType::class,
                [
                    'label'      => 'These',
                    'required'   => true,
                    'empty_data' => '',
                ]
            )
            ->add(
                'counterThesis',
                TextareaType::class,
                [
                    'label'      => 'Gegenthese/Kontrollfrage',
                    'required'   => false,
                    'empty_data' => '',
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => FeedbackQuestion::class,
                'empty_data' => function (FormInterface $form) {
                    return new FeedbackQuestion('', '');
                },
            ]
        );
    }
}
