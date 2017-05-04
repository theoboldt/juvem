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

use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Manager\NewsletterManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class NewsletterSubscriptionReminderCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:newsletter:remind')
            ->setDescription('Remind newsletter subscriptions about requirement of confirmation');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $subscriptions = $this->unconfirmedSubscriptionList();

        $output->writeln(
            sprintf("There are <info>%d</info> unconfirmed subscriptions available.", count($subscriptions))
        );

        $helper   = $this->getHelper('question');
        $question = new ConfirmationQuestion('Do you want to remind these subscribers?', false);

        if (!$helper->ask($input, $output, $question)) {
            return 0;
        }

        $progress = new ProgressBar($output, count($subscriptions));
        $progress->start();
        try {
            $sent = $this->processSubscriptions(
                $subscriptions,
                function () use ($progress) {
                    $progress->advance();
                }
            );
        } catch (\Exception $e) {
            $output->writeln('<error>Failed to process participants: ' . $e->getMessage() . '</error>');
            return 3;
        } finally {
            $progress->finish();
        }
        $output->writeln(sprintf("\n       Sent <info>%d</info> reminders for confirmation", $sent));

        return 0;
    }

    /**
     * Get list of newsletter subscriptions which are not yet confirmed
     *
     * @return array|NewsletterSubscription[]
     */
    protected function unconfirmedSubscriptionList()
    {
        $repository    = $this->getContainer()->get('doctrine')->getRepository('AppBundle:NewsletterSubscription');
        $subscriptions = $repository->findBy(['isConfirmed' => false]);

        return $subscriptions;
    }

    /**
     * Process subscriptions and send reminder email
     *
     * @param array         $subscriptions List of new recipients
     * @param null|callable $stepCallback  Callback called each time a row was read
     * @return  int                        Amount of new created subscriptions
     */
    protected function processSubscriptions(array $subscriptions, $stepCallback = null)
    {
        /** @var NewsletterManager $mailManager */
        $mailManager = $this->getContainer()->get('app.newsletter_manager');
        $sent        = 0;

        foreach ($subscriptions as $subscription) {
            $sent += $mailManager->mailNewsletterSubscriptionConfirmationReminder($subscription);
            if (is_callable($stepCallback)) {
                $stepCallback();
            }
        }
        return $sent;
    }
}
