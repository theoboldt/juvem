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

    private AppSecretSigner $signer;

    private ParticipationManager $participationManager;

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

                $filloutId = $this->provideNewFilloutId($event);
                $links[]   = $this->router->generate(
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

}
