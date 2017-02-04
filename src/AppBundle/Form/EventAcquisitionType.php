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

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventAcquisitionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'acquisitionAttributes', EntityType::class, array(
                                       'class'        => 'AppBundle\Entity\AcquisitionAttribute',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('a')
                                  ->where('a.deletedAt IS NULL')
                                  ->orderBy('a.managementTitle', 'ASC');
                    },
                                       'choice_label' => 'managementTitle',
                                       'multiple'     => true,
                                       'expanded'     => true,
                                       'label'        => 'Bei Anmeldungen zu erfassende Felder'
                                   )
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'AppBundle\Entity\Event',
            )
        );
    }
}
