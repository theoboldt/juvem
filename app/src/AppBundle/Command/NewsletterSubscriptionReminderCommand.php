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
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class NewsletterSubscriptionReminderCommand extends Command
{

    /**
     * doctrine
     *
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;
    
    /**
     * app.newsletter_manager
     *
     * @var NewsletterManager
     */
    private NewsletterManager $newsletterManager;
    
    /**
     * NewsletterSubscriptionReminderCommand constructor.
     *
     * @param ManagerRegistry $doctrine
     * @param NewsletterManager $newsletterManager
     */
    public function __construct(ManagerRegistry $doctrine, NewsletterManager $newsletterManager)
    {
        $this->doctrine          = $doctrine;
        $this->newsletterManager = $newsletterManager;
        parent::__construct();
    }
    
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
    
        $sent       = 0;
        $exceptions = [];
        $failed     = [];
        foreach ($subscriptions as $subscription) {
            try {
                $subscriptionSent = $this->newsletterManager->mailNewsletterSubscriptionConfirmationReminder(
                    $subscription
                );
                $sent             += $subscriptionSent;
            } catch (\Exception $e) {
                $exceptions[] = $e;
                $failed[]     = $subscription->getEmail();
                if (strpos($e->getMessage(), 'Connection could not be established with host') !== false
                    || strpos($e->getMessage(), 'Mails per session limit exceeded') !== false
                ) {
                    break;
                }
            }
            $progress->advance();
        }
        $progress->finish();
        if (count($exceptions)) {
            foreach ($exceptions as $exception) {
                $tpl = $output->isVeryVerbose(
                ) ? '<error>Exception while sending in file %1$s:%2$d, with message "%3$s", trace: %4$s</error>' : '<error>Exception while sending, file %1$s:%2$d, message "%3$s"</error>';
                $output->writeln(
                    sprintf(
                        $tpl,
                        $exception->getFile(),
                        $exception->getLine(),
                        $exception->getMessage(),
                        $exception->getTraceAsString()
                    )
                );
            }
        
            $output->write(sprintf("\n       Sent <info>%d</info> reminders for confirmation. ", $sent));
            $output->writeln('<error>Failed to send '.count($failed).' messages to ' . implode(', ', $failed) . '</error>');
            return 3;
        
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
        $repository    = $this->doctrine->getRepository(NewsletterSubscription::class);
        $subscriptions = $repository->findBy(['isConfirmed' => false]);

        return $subscriptions;
    }
}
