<?php

namespace AppBundle\Manager;

use AppBundle\Entity\Event;
use \AppBundle\Entity\Participation as ParticipationEntity;
use AppBundle\Entity\Participant;
use Swift_Mailer;
use Symfony\Component\Templating\EngineInterface;

class ParticipationManager
{

    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * @var EngineInterface
     */
    protected $templating;


    public function __construct(Swift_Mailer $mailer, EngineInterface $templating)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
    }

    public function mailParticipationRequested(ParticipationEntity $participation, Event $event)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject('Hello Email')
            ->setFrom('jungschar.vaihingen@gmail.com')
            ->setTo($participation->getEmail())
            ->setBody(
                $this->templating->renderView(
                    'mail/participation.txt.twig',
                    array(
                        'event'         => $event,
                        'participation' => $participation,
                        'participants'  => $participation->getParticipants()
                    )
                ),
                'text/plain'
            /*
            $this->renderView(
                'mail/participation.html.twig',
                array(
                    'salution' => $participation->getSalution(),
                    'nameLast' => $participation->getNameLast()
                )
            ),
            'text/html'
            )->addPart(
                $this->renderView(
                    'mail/participation.txt.twig',
                    array(
                        'event' => $event,
                        'participation' => $participation,
                        'participants'  => $participation->getParticipants()
                    )
                ),
                'text/plain'
            */
            );
        $this->mailer->send($message);

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