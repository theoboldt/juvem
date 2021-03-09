<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\EventListeners;


use AppBundle\Http\Annotation\CloseSessionEarly;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class CloseSessionEarlyAnnotationListener
{
    
    /**
     * @var Reader
     */
    private Reader $reader;
    
    /**
     * CloseSessionEarlyAnnotationListener constructor.
     *
     * @param Reader $reader
     * @param SessionInterface $session
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }
    
    /**
     * {@inheritdoc}
     */
    public function onKernelController(ControllerEvent $event)
    {
        if (!is_array($controllers = $event->getController())) {
            return;
        }
        
        [$controller, $methodName] = $controllers;
        
        $reflectionClass = new \ReflectionClass($controller);
        
        // Controller
        $classAnnotation = $this->reader->getClassAnnotation($reflectionClass, CloseSessionEarly::class);
        
        // Method
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $methodAnnotation = $this->reader->getMethodAnnotation($reflectionMethod, CloseSessionEarly::class);
        
        if (!($classAnnotation || $methodAnnotation)) {
            return;
        }
        
        $request = $event->getRequest();
        if ($request->hasSession()) {
            $session = $request->getSession();
            if ($session->isStarted()) {
                $session->save();
            }
        }
    }
}