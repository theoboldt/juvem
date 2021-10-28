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

use AppBundle\Entity\Employee;
use AppBundle\Entity\EventRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoveEmployeeType extends AbstractType
{
    
    const EMPLOYEE_OPTION = 'employee';
    
    const PARAM_EVENT_OLD = '{EVENT_OLD}';

    const PARAM_PID_OLD = '{GID_OLD}';
    
    const PARAM_EVENT_NEW = '{EVENT_NEW}';
    
    const PARAM_PID_NEW = '{GID_NEW}';
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $commentOld    = 'Diese:r Mitarbeiter:in wurde verschoben in die Veranstaltung "{EVENT_NEW}". Der neue Datensatz hat die Nummer #{GID_NEW}.';
        $commentNew    = 'Diese:r Mitarbeiter:in ist verschoben worden von der Veranstaltung "{EVENT_OLD}". Der alte Datensatz hatte die Nummer #{GID_OLD}.';
        $Employee = null;
        if ($options[self::EMPLOYEE_OPTION]) {
            /** @var Employee $employee */
            $employee = $options[self::EMPLOYEE_OPTION];
        }
        
        $builder
            ->add(
                'targetEvent',
                EntityType::class,
                [
                    'label' => 'Ziel-Veranstaltung',
                    'class' => 'AppBundle:Event',
                    'query_builder' => function (EventRepository $er) use ($employee) {
                        $builder = $er->createQueryBuilder('e')
                                      ->andWhere('e.isActive = 1')
                                      ->andWhere('e.deletedAt IS NULL')
                                      ->orderBy('e.title', 'ASC');
                        if ($employee) {
                            $builder->andWhere($builder->expr()->neq('e.eid', $employee->getEvent()->getEid()));
                        }
                        return $builder;
                    },
                    'choice_label' => 'title',
                    'multiple' => false,
                    'required' => true
                ]
            )
            ->add(
                'commentOldEmployee', TextareaType::class,
                ['label' => 'Kommentar für originalen Mitarbeitenden-Datensatz', 'data' => $commentOld]
            )
            ->add(
                'commentNewEmployee', TextareaType::class,
                ['label' => 'Kommentar für neuen Mitarbeitenden-Datensatz', 'data' => $commentNew]
            );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault(self::EMPLOYEE_OPTION, null);
        $resolver->setAllowedTypes(self::EMPLOYEE_OPTION, Employee::class);
    }
}
