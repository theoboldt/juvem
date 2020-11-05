<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Controller;


use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Trait FormAwareControllerTrait
 *
 * @see \Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait
 */
trait FormAwareControllerTrait
{
    /**
     * form
     *
     * @var FormFactoryInterface
     */
    private FormFactoryInterface $formFactory;
    
    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string $type
     * @param null $data
     * @param array $options
     * @return FormInterface
     */
    protected function createForm(string $type, $data = null, array $options = []): FormInterface
    {
        return $this->formFactory->create($type, $data, $options);
    }
    
    /**
     * Creates and returns a form builder instance.
     *
     * @param null $data
     * @param array $options
     * @return FormBuilderInterface
     */
    protected function createFormBuilder($data = null, array $options = []): FormBuilderInterface
    {
        return $this->formFactory->createBuilder(FormType::class, $data, $options);
    }
    
}