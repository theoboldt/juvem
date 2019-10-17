<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Form\Meal;


use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Meals\Recipe;
use AppBundle\Entity\Meals\RecipeFeedback;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class MealFeedbackType extends AbstractType
{
    
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
    
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var RecipeFeedback $data */
        $data = $options['data'] ?? null;
        
        $builder
            ->add(
                'recipe',
                EntityType::class,
                [
                    'class'        => Recipe::class,
                    'choice_label' => 'title',
                    'multiple'     => false,
                    'expanded'     => false,
                    'label'        => 'Rezept',
                    'attr'         => ['style' => 'display:none;'],
                    'label_attr'   => ['style' => 'display:none;']
                ]
            )
            ->add(
                'weight',
                ChoiceType::class,
                [
                    'label'      => 'Wertung',
                    'choices'    => [
                        'Meine Probe ist nicht repräsentativ und sollte nicht automatisch mit in die Kalkulation einfließen'                   => RecipeFeedback::WEIGHT_NONE,
                        'Meine Probe ist durchschnittlich repräsentativ (einfache Wertung)'                                                    => RecipeFeedback::WEIGHT_SINGLE,
                        'Meine Probe ist besonders repräsentativ und bildet unseren üblichen Teilnehmer-Schnitt perfekt ab (doppelte Wertung)' => RecipeFeedback::WEIGHT_DOUBLE,
                    ],
                    'empty_data' => RecipeFeedback::WEIGHT_SINGLE,
                    'expanded'   => true,
                    'multiple'   => false,
                    'required'   => true
                ]
            )
            ->add(
                'event',
                EntityType::class,
                [
                    'class'         => Event::class,
                    'choice_label'  => 'title',
                    'multiple'      => false,
                    'expanded'      => false,
                    'query_builder' => function (EventRepository $er) {
                        return $er->createQueryBuilder('e')
                                  ->where('e.deletedAt IS NULL')
                                  ->addOrderBy('e.startDate', 'DESC')
                                  ->addOrderBy('e.startTime', 'DESC')
                                  ->addOrderBy('e.title');
                    },
                    'label'         => 'Veranstaltung',
                    'required'      => false,
                ]
            )
            ->add(
                'date', DateType::class,
                [
                    'label'       => 'Datum',
                    'widget'      => 'single_text',
                    'format'      => 'yyyy-MM-dd',
                    'constraints' => new LessThanOrEqual('today 10:00')
                ]
            )
            ->add(
                'comment',
                TextType::class,
                [
                    'label'      => 'Hinweise',
                    'required'   => false,
                    'empty_data' => ''
                ]
            )
            ->add(
                'peopleCount',
                NumberType::class,
                [
                    'label'    => 'Personen',
                    'required' => true
                ]
            )
            ->add(
                'feedbackGlobal',
                RecipeItemFeedbackChoiceType::class,
                [
                    'label'    => 'Mengen-Bewertung (Insgesamt)',
                    'required' => true,
                    'data'     => $data ? $data->getFeedbackGlobal() : null,
                ]
            );
        
        
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => RecipeFeedback::class,
            ]
        );
    }
    
}