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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ModalActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('area', HiddenType::class)
            ->add('action', HiddenType::class)
            ->add('id', HiddenType::class)
            ->add(
                'cancel', ButtonType::class, array('attr'  => array('class'        => 'btn-default',
                                                                    'data-dismiss' => 'modal'
            ),
                                                   'label' => 'Abbrechen'
            )
            )
            ->add(
                'submit', SubmitType::class, array('attr' => array('class' => 'btn-primary',
                                                                   'label' => 'Speichern'
            )
            )
            );
    }
}
