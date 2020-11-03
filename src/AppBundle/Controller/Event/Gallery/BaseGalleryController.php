<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Controller\Event\Gallery;


use AppBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class BaseGalleryController extends AbstractController
{
    /**
     * Generate gallery hash for transmitted event
     *
     * @param Event $event Event
     * @return string      SHA1 hash
     */
    protected function galleryHash(Event $event)
    {
        return sha1('gallery-hash-' . $this->getParameter('kernel.secret') . $event->getEid());
    }

}