<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Controller\Event\Participation;


use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use AppBundle\Entity\Event;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class AdminGroupController extends Controller
{

    /**
     * Page for list of participants of an event having a provided age at a specific date
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("choiceFillout", class="AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption", options={"id" = "cid"})
     * @Route("/admin/event/{eid}/group/{cid}", requirements={"eid": "\d+", "cid": "\d+"}, name="admin_event_group_detail")
     * @Security("is_granted('participants_read', event)")
     */
    public function groupDetailsAction(Event $event, AttributeChoiceOption $choiceFillout)
    {
        return $this->render(
            'event/admin/group.html.twig', ['event' => $event]
        );
    }
}
