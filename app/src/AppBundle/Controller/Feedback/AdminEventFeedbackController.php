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
use AppBundle\Feedback\FeedbackManager;
use AppBundle\Feedback\FeedbackQuestionnaireAnalyzer;
use AppBundle\Form\Feedback\ImportQuestionsType;
use AppBundle\Form\Feedback\QuestionnaireFilloutType;
use AppBundle\Form\Feedback\QuestionnaireType;
use AppBundle\Http\Annotation\CloseSessionEarly;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * @Security("is_granted('ROLE_ADMIN')")
 */
class AdminEventFeedbackController
{
    use RenderingControllerTrait, FormAwareControllerTrait, DoctrineAwareControllerTrait, RoutingControllerTrait;

    private FeedbackManager $feedbackManager;

    /**
     * @param Environment $twig
     */
    public function __construct(
        Environment          $twig,
        FormFactoryInterface $formFactory,
        RouterInterface      $router,
        ManagerRegistry      $doctrine,
        FeedbackManager      $feedbackManager
    ) {
        $this->twig            = $twig;
        $this->formFactory     = $formFactory;
        $this->router          = $router;
        $this->doctrine        = $doctrine;
        $this->feedbackManager = $feedbackManager;
    }


    /**
     * @CloseSessionEarly
     * @Route("/admin/event/{eid}/feedback", requirements={"eid": "\d+"}, name="admin_feedback_event")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('read', event)")
     */
    public function eventFeedbackAction(Event $event, Request $request)
    {
        $formAction = $this->createFormBuilder()
                           ->add('action', HiddenType::class)
                           ->getForm();
        $formAction->handleRequest($request);
        if ($formAction->isSubmitted() && $formAction->isValid()) {
            $this->feedbackManager->requestFeedback($event);
            return $this->redirectToRoute('admin_feedback_event', ['eid' => $event->getEid()]);
        }
        
        $analyzer = new FeedbackQuestionnaireAnalyzer(
            $event->getFeedbackQuestionnaire(true),
            FeedbackManager::extractFilloutsFromEntities($this->feedbackManager->fetchFillouts($event))
        );

        return $this->render(
            'feedback/event-overview.html.twig',
            [
                'responseCount'          => $this->feedbackManager->fetchResponseCount($event),
                'filloutsSubmittedCount' => $this->feedbackManager->fetchFilloutSubmittedTotalCount($event),
                'answerDistribution'     => $analyzer->getQuestionAnswerDistributions(),
                'formAction'             => $formAction->createView(),
                'event'                  => $event,
                'questionnaire'          => $event->getFeedbackQuestionnaire(true),
            ]
        );
    }

    /**
     * @CloseSessionEarly
     * @Route("/admin/event/{eid}/feedback/questionnaire-configure", requirements={"eid": "\d+"},
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

    /**
     * @CloseSessionEarly
     * @Route("/admin/event/{eid}/feedback/questionnaire-import", requirements={"eid": "\d+"},
     *                                                     name="admin_feedback_event_import")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('read', event)")
     * @param Event $event
     */
    public function eventFeedbackImportQuestions(Event $event, Request $request)
    {
        $questions     = $this->feedbackManager->provideQuestions();
        $questionnaire = $event->getFeedbackQuestionnaire(true);
        $form          = $this->createForm(
            ImportQuestionsType::class,
            null,
            [
                ImportQuestionsType::QUESTIONNAIRE_OPTION => $questionnaire,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $questionnaireQuestions = $questionnaire->getQuestions();
            foreach ($form->getData() as $questionUuid => $copy) {
                if ($copy) {
                    $questionnaireQuestions[] = $questions[$questionUuid];
                }
            }
            $questionnaire->setQuestions($questionnaireQuestions);
            
            $event->setFeedbackQuestionnaire($questionnaire);
            $em->persist($event);
            $em->flush();
            return $this->redirectToRoute('admin_feedback_event_questionnaire', ['eid' => $event->getEid()]);
        }

        return $this->render(
            'feedback/event-questionnaire-question-import.html.twig',
            [
                'event'     => $event,
                'questions' => $questions,
                'form'      => $form->createView(),
            ]
        );
    }

    /**
     * @CloseSessionEarly
     * @Route("/admin/event/{eid}/feedback/test", requirements={"eid": "\d+"}, name="admin_feedback_event_test")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Security("is_granted('read', event)")
     */
    public function eventFeedbackTestQuestionnaire(Event $event)
    {
        $questionnaire = $event->getFeedbackQuestionnaire(true);
        $form          = $this->createForm(
            QuestionnaireFilloutType::class,
            null,
            [
                QuestionnaireFilloutType::QUESTIONNAIRE_OPTION => $questionnaire,
            ]
        );

        $questions = [];
        foreach ($questionnaire->getQuestions() as $question) {
            $questions['question-' . $question->getUuid()] = $question;
        }

        return $this->render(
            'feedback/event-questionnaire-fillout.html.twig',
            [
                'event'         => $event,
                'questionnaire' => $questionnaire,
                'questions'     => $questions,
                'form'          => $form->createView(),
            ]
        );

    }

}
