<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\Connector;

use AppBundle\Entity\Participant;
use AppBundle\Entity\ParticipantConnector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IndividualConnectorType extends AbstractType
{
    const OPTION_PARTICIPANT = 'participant';
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'description',
                TextType::class,
                ['label' => 'Erläuterung/Zweck des Codes', 'required' => false]
            );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::OPTION_PARTICIPANT);
        $resolver->setAllowedTypes(self::OPTION_PARTICIPANT, Participant::class);
        
        $resolver->setDefaults(
            [
                'data_class' => ParticipantConnector::class,
                'empty_data' => function (FormInterface $form) {
                    $participant = $form->getConfig()->getOption(self::OPTION_PARTICIPANT);
                    return new ParticipantConnector($participant);
                },
            ]
        );
    }
}
