<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\ParamConverter;

use AppBundle\EventNotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class EventParamConverter extends DoctrineParamConverter implements ParamConverterInterface
{

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException              When unable to guess how to get a Doctrine instance from the request information
     * @throws EventNotFoundHttpException   When object not found
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        try {
            return parent::apply($request, $configuration);
        } catch (NotFoundHttpException $e) {
            throw new EventNotFoundHttpException($e->getMessage(), $e, $e->getCode());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration)
    {
        $supported = ($configuration->getClass() == 'AppBundle:Event');
        return $supported && parent::supports($configuration);
    }
}