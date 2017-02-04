<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Command;

use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Task;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EventSubscriptionMailCommand extends ContainerAwareCommand
{
    /**
     * Event Task
     *
     * @var Task
     */
    protected $task;

    /**
     * List of qualified events
     *
     * @var Event[]
     */
    protected $eventList;

    /**
     * List of valid users who have active subscriptions
     *
     * @var User[]
     */
    protected $userList = [];

    /**
     * List identified by user id, containing an array of subscribed event ids
     *
     * @var array
     */
    protected $userSubscription = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('app:event:subscription')
             ->setDescription('Send event subscription emails')
             ->addOption('dry-run', 'd', InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dry  = $input->getOption('dry-run');
        $run  = new \DateTime();
        $task = $this->getTask();
        $em = $this->getContainer()->get('doctrine')->getManager();

        $this->executeParticipantParse($output);
        $this->executeMailSend($output, $dry);

        $task->setLastRun($run);
        $em->persist($task);
        $em->flush();
    }

    /**
     * Executes the participant parse
     *
     * @param OutputInterface $output An OutputInterface instance
     * @param bool            $dry    Set to true to not send emails
     */
    protected function executeMailSend(OutputInterface $output, $dry = false)
    {
        $participationManager = $this->getContainer()->get('app.participation_manager');

        $progress = new ProgressBar($output, count($this->userSubscription));
        $progress->start();
        foreach ($this->userList as $uid => $user) {
            $events = [];

            foreach ($this->userSubscription[$user->getUid()] as $eid) {
                $events[]   = $this->eventList[$eid];
            }
            if (!$dry) {
                $participationManager->mailSubscriptionReport($user, $events, $this->getLastTaskRun());
            }
            $progress->advance();
        }
        $progress->finish();
        $output->writeln(sprintf("\n      Created mails for <info>%d</info> users", count($this->userList)));
    }

    /**
     * Executes the participant parse
     *
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function executeParticipantParse(OutputInterface $output)
    {
        $last       = $this->getLastTaskRun();
        $repository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:Event');
        $eventList  = $repository->findWithSubscriptions();

        $progress = new ProgressBar($output, count($eventList));
        $progress->start();
        /** @var Event $event */
        foreach ($eventList as $event) {
            $participants = array_filter(
                $repository->participantsList($event), function (Participant $participant) use ($last) {
                return (!$participant->getStatus(true)->has(ParticipantStatus::TYPE_STATUS_CONFIRMED) &&
                        $participant->getCreatedAt() > $last);
            }
            );
            if (!count($participants)) {
                continue;   //no qualified participants
            }

            $this->eventList[$event->getEid()] = [
                'event'        => $event,
                'participants' => $participants
            ];

            /** @var User $user */
            foreach ($event->getSubscribers() as $user) {
                if ($user->isAccountNonExpired() && $user->isAccountNonLocked() && $user->isEnabled() &&
                    $user->hasRole(User::ROLE_ADMIN_EVENT)
                ) {
                    if (!isset($this->userList[$user->getUid()])) {
                        $this->userList[$user->getUid()] = $user;
                    }
                    $this->userSubscription[$user->getUid()][] = $event->getEid();
                }
            }
            $progress->advance();
        }
        $progress->finish();
        $output->writeln(sprintf("\n      Checked <info>%d</info> events", count($eventList)));
    }

    /**
     * Fetch @see Task corresponding to this command
     *
     * @return Task
     */
    protected function getTask()
    {
        if (!$this->task) {
            $repository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:Task');
            $this->task = $repository->findOneBy(['command' => $this->getName()]);
            if (!$this->task) {
                $this->task = new Task();
                $this->task->setCommand($this->getName());
            }
        }
        return $this->task;
    }

    /**
     * Get last execution time of task or @see \DateTime('2017-01-01')
     *
     * @return \DateTime
     */
    protected function getLastTaskRun()
    {
        return $this->getTask()->getLastRun() ? $this->getTask()->getLastRun() : new \DateTime('2017-01-01');
    }
}
