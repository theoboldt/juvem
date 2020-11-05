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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Bic;
use Symfony\Component\Validator\Constraints\Iban;

class BankAccountType extends AbstractType
{

    const FIELD_NAME_BIC = 'bankAccountBic';
    const FIELD_NAME_IBAN = 'bankAccountIban';
    const FIELD_NAME_OWNER = 'bankAccountOwner';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('class', 'bank-account');
        $builder
            ->add(
                self::FIELD_NAME_BIC,
                TextType::class,
                [
                    'label'       => 'BIC-Code (Bankverbindung)',
                    'required'    => false,
                    'constraints' => [new Bic()],
                    'attr'        => ['class' => 'bank-account-bic'],
                ]
            )
            ->add(
                self::FIELD_NAME_IBAN,
                TextType::class,
                [
                    'label'       => 'IBAN (Bankverbindung)',
                    'required'    => false,
                    'constraints' => [new Iban()],
                    'attr'        => ['class' => 'bank-account-iban'],
                ]
            )
            ->add(
                self::FIELD_NAME_OWNER,
                TextType::class,
                [
                    'label'       => 'Kontoinhaber (Bankverbindung)',
                    'required'    => false,
                    'attr'        => ['class' => 'col-md-3 bank-account-owner'],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }
}
