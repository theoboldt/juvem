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

use AppBundle\Entity\Event;
use AppBundle\Manager\Invoice\InvoiceMailingConfiguration;
use AppBundle\PdfConverterService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceMailingType extends AbstractType
{
    const EVENT_FIELD = 'event';

    /**
     * PDF converter if configured
     *
     * @var PdfConverterService|null
     */
    private $pdfConverter;

    /**
     * InvoiceMailingType constructor.
     *
     * @param PdfConverterService|null $pdfConverter
     */
    public function __construct(?PdfConverterService $pdfConverter = null)
    {
        $this->pdfConverter = $pdfConverter;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fileTypeChoices = [
            InvoiceMailingConfiguration::FILE_TYPE_WORD_LABEL => InvoiceMailingConfiguration::FILE_TYPE_WORD,
        ];
        if ($this->pdfConverter) {
            $fileTypeChoices[InvoiceMailingConfiguration::FILE_TYPE_PDF_LABEL]
                = InvoiceMailingConfiguration::FILE_TYPE_PDF;
        }

        $builder
            ->add(
                'filter',
                ChoiceType::class,
                [
                    'label'    => 'Filter',
                    'choices'  => [
                        InvoiceMailingConfiguration::SEND_ALL_LABEL => InvoiceMailingConfiguration::SEND_ALL_FILTER,
                        InvoiceMailingConfiguration::SEND_NEW_LABEL => InvoiceMailingConfiguration::SEND_NEW_FILTER,
                    ],
                    'required' => true,
                    'expanded' => true,
                ]
            )->add(
                'fileType',
                ChoiceType::class,
                [
                    'label'    => 'Datei-Typ der Rechnung',
                    'choices'  => $fileTypeChoices,
                    'required' => true,
                    'expanded' => true,
                ]
            )
            ->add('message', TextareaType::class, ['label' => 'Nachricht', 'attr' => ['described-by' => 'invoiceMailingMessageHelp']]);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::EVENT_FIELD);
        $resolver->setAllowedTypes(self::EVENT_FIELD, Event::class);

        $resolver->setDefaults(
            [
                'data_class' => InvoiceMailingConfiguration::class,
                'empty_data' => function (FormInterface $form) {
                    $event = $form->getConfig()->getOption(self::EVENT_FIELD);
                    return new InvoiceMailingConfiguration($event);
                },
            ]
        );
    }
}
