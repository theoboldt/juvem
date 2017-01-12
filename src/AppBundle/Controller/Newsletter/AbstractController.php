<?php
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