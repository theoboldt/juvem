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


use AppBundle\Entity\AcquisitionAttribute\Fillout;
use AppBundle\Form\Transformer\AcquisitionAttributeFilloutTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

trait AcquisitionAttributeIncludingTypeTrait
{

    /**
     * Add acquisition attribute fields to form depending on data
     *
     * @param FormBuilderInterface $builder    Builder where to add forms
     * @param array                $attributes Acquisition attributes to check
     * @param mixed                $data       Data of form
     */
    protected function addAcquisitionAttributesToBuilder(
        FormBuilderInterface $builder,
        array $attributes,
        $data
    ) {
        $attributeTransformer = new AcquisitionAttributeFilloutTransformer();

        /** @var \AppBundle\Entity\AcquisitionAttribute\Attribute $attribute */
        foreach ($attributes as $attribute) {
            $bid = $attribute->getBid();

            $attributeOptions = $attribute->getFieldOptions();
            if ($attribute->isMultipleChoiceType()) {
                $attributeOptions['empty_data'] = [];
            }

            try {
                if ($data instanceof EntityHavingFilloutsInterface) {
                    /** @var Fillout $fillout */
                    $fillout                  = $data->getAcquisitionAttributeFillout($bid);
                    $attributeOptions['data'] = $fillout->getValue()->getFormValue();
                }
            } catch (\OutOfBoundsException $e) {
                //intentionally left empty
            }

            if ($attribute->isRequired()) {
                switch ($attribute->getFieldType()) {
                    case ChoiceType::class:
                        if ($attribute->isMultipleChoiceType()) {
                            $message = 'Mindestens eine Option muss ausgewählt sein.';
                        } else {
                            $message = 'Eine Option muss ausgewählt sein.';
                        }
                        $attributeOptions['constraints'] = [
                            new NotBlank(['message' => $message])
                        ];
                        break;
                    default:
                        $attributeOptions['constraints'] = [
                            new NotBlank()
                        ];
                        break;
                }
            }

            $attributeOptions['attr'] = ['data-typeahead-source' => $attribute->getName()];

            $builder->add(
                $attribute->getName(),
                $attribute->getFieldType(),
                array_merge($attributeOptions, $attribute->getFieldOptions())
            );
            $builder->get($attribute->getName())->addModelTransformer($attributeTransformer);
        }

    }

}
