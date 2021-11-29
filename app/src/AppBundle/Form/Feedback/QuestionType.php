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
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'internalTitle',
                TextType::class,
                [
                    'label'    => 'Interner Kurztitel der Frage',
                    'required' => true,
                    'attr'     => ['aria-describedby' => 'help-internal-title'],
                ]
            )
            ->add(
                'questionText',
                TextareaType::class,
                [
                    'label'    => 'Frage',
                    'required' => true,
                    'attr'     => ['aria-describedby' => 'help-question'],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => FeedbackQuestion::class,
            ]
        );
    }
}
