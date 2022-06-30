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
use AppBundle\Controller\FlashBagAwareControllerTrait;
use AppBundle\Controller\FormAwareControllerTrait;
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Controller\RoutingControllerTrait;
use AppBundle\Entity\Event;
use AppBundle\Feedback\FeedbackManager;
use AppBundle\Feedback\FeedbackQuestionnaireFillout;
use AppBundle\Form\Feedback\QuestionnaireFilloutType;
use AppBundle\Http\Annotation\CloseSessionEarly;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class PublicEventFeedbackController
{
    use RenderingControllerTrait, FormAwareControllerTrait, DoctrineAwareControllerTrait, RoutingControllerTrait, FlashBagAwareControllerTrait;

    private FeedbackManager $feedbackManager;

    /**
     * @param Environment $twig
     */
    public function __construct(
        Environment          $twig,
        FormFactoryInterface $formFactory,
        RouterInterface      $router,
        ManagerRegistry      $doctrine,
        SessionInterface     $session,
        FeedbackManager      $feedbackManager
    ) {
        $this->twig            = $twig;
        $this->formFactory     = $formFactory;
        $this->router          = $router;
        $this->doctrine        = $doctrine;
        $this->session         = $session;
        $this->feedbackManager = $feedbackManager;
    }

    /**
     * @param Event  $event
     * @param string $collection
     * @param string $signature
     * @return Response|null
     */
    private function createErrorResponseIfRequired(Event $event, string $collection, string $signature): ?Response
    {
        if (!$this->feedbackManager->isCollectionsSignatureValid($event->getId(), $collection, $signature)) {
            return $this->render(
                'feedback/public/event-questionnaire-alert.html.twig',
                [
                    'event'   => $event,
                    'type'    => 'danger',
                    'message' => 'Der Link, dem Sie zum Fragebogen gefolgt sind, scheint nicht ganz vollständig zu sein. Bitte überprüfen Sie den Link, und rufen Sie die Seite erneut auf.',
                ],
                (new Response('', Response::HTTP_BAD_REQUEST))
            );
        }
        if (!$event->hasFeedbackQuestionnaire()) {
            return $this->render(
                'feedback/public/event-questionnaire-alert.html.twig',
                [
                    'event'   => $event,
                    'type'    => 'warning',
                    'message' => 'Für die Veranstaltung ist kein Fragebogen konfiguriert. Wenn Sie trotzdem eine Rückmeldung abgeben möchten, schreiben Sie uns am Besten eine E-Mail oder rufen Sie uns an.',
                ]
            );
        }
        $questionnaire = $event->getFeedbackQuestionnaire(true);

        //Todo: Validate if questionnaire is active and accepts input

        return null;
    }


    /**
     * @CloseSessionEarly
     * @Route("/event/{eid}/feedback/a/{collection}/{signature}",
     *     requirements={"eid": "\d+", "collection": "\d+","signature": "\b[A-Fa-f0-9]{64}\b"},
     *     name="feedback_event_collect_participant")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @param Event   $event
     * @param int     $collection
     * @param string  $signature
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function collectForSingleParticipant(Event $event, int $collection, string $signature, Request $request)
    {
        $response = $this->createErrorResponseIfRequired($event, (string)$collection, $signature);
        if ($response) {
            return $response;
        }
        $repository = $this->getDoctrine()->getRepository(
            \AppBundle\Entity\FeedbackQuestionnaire\FeedbackQuestionnaireFillout::class
        );
        /** @var \AppBundle\Entity\FeedbackQuestionnaire\FeedbackQuestionnaireFillout $fillout */
        $fillout = $repository->find($collection);
        $data    = $fillout->getFillout(true) ?? new FeedbackQuestionnaireFillout();

        $questionnaire = $event->getFeedbackQuestionnaire(true);
        $form          = $this->createForm(
            QuestionnaireFilloutType::class,
            $data,
            [
                QuestionnaireFilloutType::QUESTIONNAIRE_OPTION => $questionnaire,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fillout->setFillout($data);
            $em = $this->getDoctrine()->getManager();
            $em->persist($fillout);
            $em->flush();
            $this->addFlash(
                'success',
                'Die Antworten wurden gespeichert! Vielen Dank fürs ausfüllen, das hilft uns sehr. Während der Fragebogen geöffnet ist, können die gegebenen Antworten jederzeit geändert werden.'
            );
        }
        $questions = [];
        foreach ($questionnaire->getQuestions() as $question) {
            $questions['question-' . $question->getUuid()] = $question;
        }
        $emptyResponse = $request->query->getBoolean('empty_response');
        if ($emptyResponse) {
            return new Response('', Response::HTTP_NO_CONTENT);
        } else {
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


    /**
     * @CloseSessionEarly
     * @Route("/event/{eid}/feedback/p/{collections}/{signature}",
     *     requirements={"eid": "\d+", "collections": "[0-9\-]+","signature": "\b[A-Fa-f0-9]{64}\b"},
     *     name="feedback_event_collect_participants")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @param Event   $event
     * @param string  $collections
     * @param string  $signature
     * @param Request $request
     * @return Response
     */
    public function collectForMultipleParticipants(
        Event   $event,
        string  $collections,
        string  $signature,
        Request $request
    ) {
        $response = $this->createErrorResponseIfRequired($event, $collections, $signature);
        if ($response) {
            return $response;
        }
        $collections = explode('-', $collections);
        foreach ($collections as &$collection) {
            $collection = (int)$collection;
        }
        unset($collection);
        $repository = $this->getDoctrine()->getRepository(
            \AppBundle\Entity\FeedbackQuestionnaire\FeedbackQuestionnaireFillout::class
        );
        /** @var \AppBundle\Entity\FeedbackQuestionnaire\FeedbackQuestionnaireFillout $fillout */
        $fillout = $repository->find(reset($collections));
        $data    = $fillout->getFillout(true) ?? new FeedbackQuestionnaireFillout();

        $questionnaire = $event->getFeedbackQuestionnaire(true);
        $form          = $this->createForm(
            QuestionnaireFilloutType::class,
            $data,
            [
                QuestionnaireFilloutType::QUESTIONNAIRE_OPTION => $questionnaire,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($collections as $collection) {
                $fillout = $repository->find($collection);
                $fillout->setFillout($data);
                $em = $this->getDoctrine()->getManager();
                $em->persist($fillout);
            }
            $em->flush();
            
            $message = 'Die Antworten wurden ';
            if (count($collections) > 1) {
                $message .= 'für alle ' . count($collections).' Fragebögen ';
            }
            $message .= 'gespeichert, vorhandene Daten wurden gegebenfalls überschrieben. Vielen Dank fürs ausfüllen, das hilft uns sehr. Während der Fragebogen für Einsendung geöffnet ist, können die gegebenen Antworten jederzeit geändert werden.';
            $this->addFlash('success', $message);
        }
        $questions = [];
        foreach ($questionnaire->getQuestions() as $question) {
            $questions['question-' . $question->getUuid()] = $question;
        }

        $emptyResponse = $request->query->getBoolean('empty_response');
        if ($emptyResponse) {
            return new Response('', Response::HTTP_NO_CONTENT);
        } else {
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

}
