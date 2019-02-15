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

use AppBundle\SerializeJsonResponse;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseSerializerListener
{
    /**
     * JMS Serializer
     *
     * @var Serializer
     */
    private $serializer;
    
    /**
     * ResponseSerializerListener constructor.
     *
     * @param Serializer $serializer JMS Serializer
     */
    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }
    
    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        
        if ($response instanceof SerializeJsonResponse) {
            $response->setJson($this->serializer->serialize($response->getUnserialized(), 'json'));
        }
    }
    
}