<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Newsletter;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractController extends Controller
{

    /**
     * Throws an exception if newsletter feature is disabled
     *
     * @return void
     * @throws NotFoundHttpException    If feature is disabled
     */
    public function dieIfNewsletterNotEnabled()
    {
        if (!$this->container->getParameter('feature.newsletter')) {
            throw new NotFoundHttpException('Newsletter feature is disabled');
        }
    }
}