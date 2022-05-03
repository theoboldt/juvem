<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Feedback;

use AppBundle\Entity\Event;
use AppBundle\Entity\FeedbackQuestionnaire\FeedbackQuestionnaireFillout;
use AppBundle\Entity\Participation;
use AppBundle\Mail\MailSendService;
use AppBundle\Manager\ParticipationManager;
use AppBundle\Security\AppSecretSigner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class FeedbackManager
{

    private EntityManagerInterface $em;

    /**
     * router
     *
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @var AppSecretSigner 
     */
    private AppSecretSigner $signer;

    /**
     * @var ParticipationManager 
     */
    private ParticipationManager $participationManager;

    /**
     * Caches all {@see FeedbackQuestion} part of default set or other questionnaires
     *
     * @var FeedbackQuestion[]|null
     * @see provideQuestions()
     */
    private ?array $questionCache = null;

    /**
     * Caches which question (identified by UUID) is used by which event)
     *
     * @var array|null
     */
    private ?array $questionUsageCache = null;

    /**
     * @var MailSendService
     */
    private MailSendService $mailService;

    /**
     * @param EntityManagerInterface $em
     * @param RouterInterface        $router
     * @param AppSecretSigner        $signer
     * @param ParticipationManager   $participationManager
     * @param MailSendService        $mailService
     */
    public function __construct(
        EntityManagerInterface $em,
        RouterInterface        $router,
        AppSecretSigner        $signer,
        ParticipationManager   $participationManager,
        MailSendService        $mailService
    ) {
        $this->em                   = $em;
        $this->router               = $router;
        $this->signer               = $signer;
        $this->participationManager = $participationManager;
        $this->mailService          = $mailService;
    }


    /**
     * Request feedback from participations via email
     *
     * @param Event $event
     * @return void
     */
    public function requestFeedback(Event $event)
    {
        $eid           = $event->getEid();
        $questionnaire = $event->getFeedbackQuestionnaire(true);

        /** @var Participation $participation */
        foreach ($event->getParticipations() as $participation) {
            if (!$participation->isConfirmed()
                || $participation->isRejected()
                || $participation->isWithdrawn()
                || $participation->getDeletedAt() !== null
            ) {
                continue;
            }

            $links       = [];
            $collections = [];
            foreach ($participation->getParticipants() as $participant) {
                if (!$participant->isConfirmed()
                    || $participant->isRejected()
                    || $participant->isWithdrawn()
                    || $participant->getDeletedAt() !== null
                ) {
                    continue;
                }

                $filloutId     = $this->provideNewFilloutId($event);
                $links[]       = $this->router->generate(
                    'feedback_event_collect_participant',
                    [
                        'eid'        => $eid,
                        'collection' => $filloutId,
                        'signature'  => $this->createCollectionsSignature($eid, (string)$filloutId),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $collections[] = $filloutId;
            }
            if (!count($links)) {
                continue;
            }

            $collections    = implode('-', $collections);
            $collectionLink = $this->router->generate(
                'feedback_event_collect_participants',
                [
                    'eid'         => $eid,
                    'collections' => $collections,
                    'signature'   => $this->createCollectionsSignature($eid, $collections),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $subject = 'Feedback zu ' . $event->getTitle();
            $title   = 'Eure Meinung zählt!';
            $lead    = $subject;

            $content = '
Liebe Familie ' . $participation->getNameLast() . '!
            
Schön, dass ihr an **' . strip_tags($event->getTitle()) . '** teilgenommen habt.
            
Wir hoffen, es hat euch gefallen. Doch am liebsten würden wir es genau 
wissen - deshalb haben wir einen *Fragebogen* vorbereitet. Damit könnt ihr 
dazu beitragen, dass die nächste Veranstaltung noch besser wird. 

Die Beantwortung des Fragebogens erfolgt anonym, dennoch erhalten alle 
Teilnehmenden einen eigenen Link zum ausfüllen. Das Ausfüllen kann bis der 
Fragebogen geschlossen wird jederzeit unterbrochen und wieder aufgenommen
werden. Am besten schaut ihr einfach mal rein!';

            if (count($links) > 1) {
                $content .= '

Da ihr mehrere Teilnehmer:innen angemeldet habt, könnt ihr entweder 
den Fragebogen für alle gemeinsam ausfüllen: 
[Fragebogen für alle gemeinsam ausfüllen](' . $collectionLink . ')

Oder jede:r Teilnehmer:in füllt einen eigenen Fragebogen aus:

';

                $linkNumber = 1;
                foreach ($links as $link) {
                    $content .= '* [Fragebogen Teilnehmer:in ' . $linkNumber . '](' . $link . ")\n";
                    ++$linkNumber;
                }
                $content .= "\n";
            }

            if ($questionnaire->getIntroductionEmail()) {
                $content .= $questionnaire->getIntroductionEmail();
            }

            $content .= '

Vielen Dank für eure Mithilfe!';

            $dataBoth = [
                'text' => [
                    'content' => $content,
                    'subject' => $subject,
                    'title'   => $title,
                    'lead'    => $lead,
                ],
                'html' => [
                    'content'             => strip_tags($content),
                    'subject'             => $subject,
                    'title'               => $title,
                    'lead'                => $lead,
                    'calltoactioncontent' => 'Jetzt den <a href="' . $collectionLink .
                                             '">Feedback Fragebogen ausfüllen</a>!',
                ],
            ];

            $message = $this->mailService->getTemplatedMessage('general-markdown', $dataBoth);

            $message->setTo(
                $participation->getEmail(),
                $participation->fullname()
            );
            MailSendService::addRelatedEntityMessageHeader(
                $message, Participation::class, $participation->getPid()
            );
            MailSendService::addRelatedEntityMessageHeader(
                $message, Event::class, $event->getEid()
            );

            $this->mailService->send($message);

        } //foreach participation
        $event->setIsFeedbackQuestionnaireSent(true);
        $this->em->persist($event);
        $this->em->flush();
    }

    public function createCollectionsSignature(int $eid, string $collections): string
    {
        return $this->signer->signArray(
            [
                'eid'         => $eid,
                'collections' => $collections,
            ]
        );
    }

    /**
     * Check if collection signature is valid
     *
     * @param int    $eid
     * @param string $collections
     * @param string $signature
     * @return bool
     */
    public function isCollectionsSignatureValid(int $eid, string $collections, string $signature): bool
    {
        return $this->signer->isArrayValid(
            [
                'eid'         => $eid,
                'collections' => $collections,
            ],
            $signature
        );
    }

    /**
     * Create new {@see \AppBundle\Entity\FeedbackQuestionnaire\FeedbackQuestionnaireFillout} instance, get it's id
     *
     * @param Event $event
     * @return int
     */
    private function provideNewFilloutId(Event $event): int
    {
        $fillout = new FeedbackQuestionnaireFillout($event);
        $this->em->persist($fillout);
        $this->em->flush();
        return $fillout->getId();
    }

    /**
     * Provide generic, pre-configured question set
     *
     * @return array
     */
    public function provideGenericQuestions(): array
    {
        $questions = [
            new FeedbackQuestion(
                'Essen',
                'Das Essen hat meistens gut geschmeckt.',
                'Das Essen war nicht gut.',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_POSITIVE,
                'ce7990d4-c4be-3532-925a-ec8e9d5ade0e'
            ),
            new FeedbackQuestion(
                'Wiederholen',
                'Wenn ich könnte, würde ich beim nächstes Mal wieder dabei sein wollen.',
                'Auch wenn noch mal mit dürfte, möchte beim nächsten Mal ich nicht kommen.',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_POSITIVE,
                '9f28a002-4d72-306d-86be-165b476bdc7e'
            ),
            new FeedbackQuestion(
                'Angebot',
                'Ich habe gerne beim Programm (die Aktionen, die Spiele) mitgemacht.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_POSITIVE,
                '1a0313ab-57a5-33ad-9c77-6bf4cfbc1943'
            ),
            new FeedbackQuestion(
                'Mitbestimmung',
                'Ich konnte selbst mitbestimmen und meine Meinung wurde gehört.',
                'Ich durfte (z.B. beim Programm) nicht mitreden und mitentscheiden.',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_POSITIVE,
                '75e81f95-3bff-3741-9c96-11eb9db6f73a'
            ),
            new FeedbackQuestion(
                'Langeweile',
                'Ich habe mich oft gelangweilt.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_NEGATIVE,
                '71d043fa-33fc-3e7c-af4e-01cd72bbe2f3'
            ),
            new FeedbackQuestion(
                'Mitarbeitende (Nett)',
                'Die meisten Mitarbeitenden waren überwiegend freundlich und nett.',
                'Die Mitarbeitenden waren unfreundlich.',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_POSITIVE,
                '2e2d8d02-ca9d-38dc-bcba-dd911702c5c4'
            ),
            new FeedbackQuestion(
                'Mitarbeitende (Hilfe)',
                'Ich habe Mitarbeitende gefunden, denen ich vertrauen konnte und die mir bei Problemen halfen.',
                'Ich konnte keinem der Mitarbeitenden richtig vertrauen.',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_POSITIVE,
                'c6fee88d-31e8-3cfb-90f6-bcdd8667ac55'
            ),
            new FeedbackQuestion(
                'Einsamkeit',
                'Ich habe mich manchmal einsam gefühlt.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_NEGATIVE,
                '8cc30a44-5d9b-3847-a9f4-3b29245dc6c6'
            ),
            new FeedbackQuestion(
                'Heimweh',
                'Ich hatte manchmal Heimweh.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_NEGATIVE,
                'f3195a8b-ec76-338d-b5ff-1481161c95fe'
            ),
            new FeedbackQuestion(
                'Ärger (Gruppe)',
                'Es gab oft Ärger und Streit in unserer Gruppe.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_NEGATIVE,
                '1228109e-ca0d-39db-a374-4db811e91101'
            ),
            new FeedbackQuestion(
                'Ärger (selbst)',
                'Ich wurde viel geärgert.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_NEGATIVE,
                '57262d4e-efd6-33cf-bce4-1e626deb3ecd'
            ),
            new FeedbackQuestion(
                'Strenge',
                'Die Regeln waren zu streng.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_NEGATIVE,
                '7568094e-7440-33c4-bbeb-8e9d5626568d'
            ),
            new FeedbackQuestion(
                'Freunde',
                'Ich habe neue Freundinnen & Freunde kennengelernt.',
                'Ich habe niemanden gefunden, mit dem ich mich gut verstanden habe.',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_POSITIVE,
                '55bd8739-296c-3eee-8a52-acc9c71f8ea2'
            ),
            new FeedbackQuestion(
                'Zufriedenheit',
                'Ich habe viele schöne Erlebnisse gehabt.',
                'Die meiste Zeit hat mir nicht gefallen.',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_POSITIVE,
                'af77b5e1-7b6f-361e-9915-ad535d716c47'
            ),
            new FeedbackQuestion(
                'Gruppe',
                'Ich habe mich in der Gruppe wohlgefühlt.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_POSITIVE,
                '075186ac-2dcf-31da-8fbc-efda67cff4f9'
            ),
            new FeedbackQuestion(
                'Bewegung',
                'Ich habe mich viel bewegt & Sport gemacht.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_POSITIVE,
                '97a56947-c11e-3ac8-b69e-10f316e0e0fd'
            ),
            new FeedbackQuestion(
                'Selbstreflexion',
                'Ich habe oft über mich selbst nachgedacht.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_NEUTRAL,
                '3201b6ce-c64f-3035-8986-f33c7b6517e3'
            ),
            new FeedbackQuestion(
                'Mehr Ausflüge',
                'Ich hätte mir mehr Ausflüge oder Unternehmungen gewünscht.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_NEUTRAL,
                '58fe375c-893e-3153-bde2-d9326b0d9b54'
            ),
            new FeedbackQuestion(
                'Mehr Sport',
                'Ich hätte mir mehr Sportangebote gewünscht.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_NEUTRAL,
                '8c93f812-ca5f-39a8-b190-93e2ee737fd2'
            ),
            new FeedbackQuestion(
                'Mehr Musik',
                'Ich hätte gern mehr gesungen und Musik gemacht.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_NEUTRAL,
                '7681e805-6212-3892-89e9-191f1603aaad'
            ),
            new FeedbackQuestion(
                'Erstes Mal',
                'Ich war zum ersten Mal mit euch auf Freizeit.',
                '',
                FeedbackQuestion::TYPE_AGREEMENT,
                FeedbackQuestion::INTERPRETATION_NEUTRAL,
                '9e1d87f9-b9dc-3ac8-bded-638b97a67e07'
            ),
        ];

        $result = [];
        /** @var FeedbackQuestion $question */
        foreach ($questions as $question) {
            $result[$question->getUuid()] = $question;
        }
        return $result;
    }

    /**
     * Provide all event related and other questions
     *
     * @return FeedbackQuestion[]
     */
    public function provideQuestions(): array
    {
        if ($this->questionCache === null) {
            $this->questionCache      = $this->provideGenericQuestions();
            $this->questionUsageCache = [];

            $eventRepository = $this->em->getRepository(Event::class);
            $events          = $eventRepository->findAllHavingQuestionnaire();

            foreach ($events as $event) {
                $questionnaire  = $event->getFeedbackQuestionnaire(true);
                $eventQuestions = $questionnaire->getQuestions();
                foreach ($eventQuestions as $eventQuestion) {
                    $eventQuestionUuid = $eventQuestion->getUuid();

                    if (isset($this->questionCache[$eventQuestionUuid])) {
                        continue;
                    }
                    foreach ($this->questionCache as $question) {
                        if ($eventQuestion->isSameAs($question)) {
                            $this->questionUsageCache[$question->getUuid()][] = $event;
                            continue 2;
                        }
                    }

                    $this->questionCache[$eventQuestionUuid]        = $eventQuestion;
                    $this->questionUsageCache[$eventQuestionUuid][] = $event;
                }
            }

        }

        return $this->questionCache;
    }

    /**
     * Get question usage for transmitted question
     *
     * @param string|null $questionUuid
     * @return Event[]
     */
    public function provideQuestionUsage(?string $questionUuid = null): array
    {
        if ($this->questionUsageCache === null) {
            $this->provideQuestions();
        }
        if ($questionUuid === null) {
            return $this->questionUsageCache;
        } else {
            return $this->questionUsageCache[$questionUuid] ?? [];
        }
    }

}
