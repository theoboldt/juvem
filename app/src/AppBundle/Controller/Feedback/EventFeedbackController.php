<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Feedback;

use AppBundle\Controller\DoctrineAwareControllerTrait;
use AppBundle\Controller\FormAwareControllerTrait;
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Controller\RoutingControllerTrait;
use AppBundle\Entity\Event;
use AppBundle\Form\Feedback\QuestionnaireType;
use AppBundle\Http\Annotation\CloseSessionEarly;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * @Security("is_granted('ROLE_ADMIN')")
 */
class EventFeedbackController
{
    use RenderingControllerTrait, FormAwareControllerTrait, DoctrineAwareControllerTrait, RoutingControllerTrait;

    /**
     * @param Environment $twig
     */
    public function __construct(
        Environment          $twig,
        FormFactoryInterface $formFactory,
        RouterInterface      $router,
        ManagerRegistry      $doctrine
    ) {
        $this->twig        = $twig;
        $this->formFactory = $formFactory;
        $this->router      = $router;
        $this->doctrine    = $doctrine;
    }


    /**
     * @CloseSessionEarly
     * @Route("/admin/event/{eid}/feedback", requirements={"eid": "\d+"}, name="admin_feedback_event")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('read', event)")
     */
    public function eventFeedbackAction(Event $event)
    {
        return $this->render(
            'feedback/event-overview.html.twig',
            [
                'event'         => $event,
                'questionnaire' => $event->getFeedbackQuestionnaire(true),
            ]
        );
    }

    /**
     * @CloseSessionEarly
     * @Route("/admin/event/{eid}/feedback/questionnaire", requirements={"eid": "\d+"},
     *                                                     name="admin_feedback_event_questionnaire")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('read', event)")
     * @param Event $event
     */
    public function eventFeedbackConfigureQuestionnaire(Event $event, Request $request)
    {
        $questionnaire = $event->getFeedbackQuestionnaire(true);
        $form          = $this->createForm(QuestionnaireType::class, $questionnaire);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $event->setFeedbackQuestionnaire($questionnaire);
            $em->persist($event);
            $em->flush();
            return $this->redirectToRoute('admin_feedback_event', ['eid' => $event->getEid()]);
        }

        return $this->render(
            'feedback/event-questionnaire.html.twig',
            [
                'event' => $event,
                'form'  => $form->createView(),
            ]
        );
    }


}
