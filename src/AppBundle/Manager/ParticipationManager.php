<?php

namespace AppBundle\Manager;

use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation as ParticipationEntity;
use AppBundle\Entity\Participation;
use AppBundle\Twig\MailGenerator;
use Swift_Mailer;
use Symfony\Component\Templating\EngineInterface;

class ParticipationManager
{

    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * @var MailGenerator
     */
    protected $mailGenerator;

    /**
     * Initiate a participation manager service
     *
     * @param Swift_Mailer  $mailer
     * @param MailGenerator $mailGenerator
     */
    public function __construct(Swift_Mailer $mailer, MailGenerator $mailGenerator)
    {
        $this->mailer        = $mailer;
        $this->mailGenerator = $mailGenerator;
    }

    /**
     * Send a participation request email
     *
     * @param ParticipationEntity $participation
     * @param Event               $event
     */
    public function mailParticipationRequested(ParticipationEntity $participation, Event $event)
    {
        $this->mailEventParticipation('participation', $participation, $event);
    }

    /**
     * Send a participation confirmation request email
     *
     * @param ParticipationEntity $participation
     * @param Event               $event
     */
    public function mailParticipationConfirmed(ParticipationEntity $participation, Event $event)
    {
        $this->mailEventParticipation('participation-confirm', $participation, $event);
    }

    /**
     * Send a participation email, containing participation and event information
     *
     * @param string              $template The message template to use
     * @param ParticipationEntity $participation
     * @param Event               $event
     */
    protected function mailEventParticipation($template, ParticipationEntity $participation, Event $event)
    {
        $message = $this->mailGenerator->getMessage(
            $template, array(
                         'event'         => $event,
                         'participation' => $participation,
                         'participants'  => $participation->getParticipants()
                     )
        );
        $message->setTo($participation->getEmail(), Participation::fullname($participation->getNameLast(), $participation->getNameFirst()));

        $this->mailer->send($message);
    }

    /**
     * Send a participation email, containing participation and event information
     *
     * @param array $data  The custom text for email
     * @param Event $event The event
     */
    public function mailEventParticipants(array $data, Event $event)
    {
        $dataText = array();
        $dataHtml = array();

        $content = null;
        foreach ($data as $area => $content) {
            $content = str_replace('{EVENT_TITLE}', $event->getTitle(), $content);

            $dataText[$area] = strip_tags($content);

            $contentHtml = htmlentities($content);

            if ($area == 'content') {
                $contentHtml = str_replace(array("\n\n", "\r\r", "\r\n\r\n"), '</p><p>', $contentHtml);
            }

            $dataHtml[$area] = $contentHtml;
        }
        unset($content);

        /** @var Participation $participation */
        foreach ($event->getParticipations() as $participation) {
            if ($participation->isConfirmed()) {
                $dataBoth = array('text' => $dataText,
                                  'html' => $dataHtml
                );

                $contentList = null;
                foreach ($dataBoth as $type => &$contentList) {
                    $content = null;
                    foreach ($contentList as $area => &$content) {
                        $content = str_replace('{PARTICIPATION_SALUTION}', $participation->getSalution(), $content);
                        $content = str_replace('{PARTICIPATION_NAME_LAST}', $participation->getNameLast(), $content);
                    }
                    unset($content);
                }
                unset($contentList);

                $message = $this->mailGenerator->getMessage(
                    'general-raw', $dataBoth
                );
                $message->setTo(
                    $participation->getEmail(),
                    Participant::fullname($participation->getNameLast(), $participation->getNameFirst())
                );

                $this->mailer->send($message);

            }
        }

    }

    /**
     * @param Swift_Mailer $mailer
     */
    public function setMailer(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param EngineInterface $templating
     */
    public function setTemplating(EngineInterface $templating)
    {
        $this->templating = $templating;
    }
}