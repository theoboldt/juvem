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

use AppBundle\Entity\Event;
use AppBundle\Manager\RelatedParticipantsFinder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateRelatedParticipantsCommand extends Command
{
    const NAME = 'app:event:calculate-related';
    
    /**
     * doctrine
     *
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;

    /**
     * @var RelatedParticipantsFinder
     */
    private RelatedParticipantsFinder $relatedParticipantsFinder;

    /**
     * CleanupUserRegistrationRequestsCommand constructor.
     *
     * @param ManagerRegistry           $doctrine
     * @param RelatedParticipantsFinder $relatedParticipantsFinder
     */
    public function __construct(ManagerRegistry $doctrine, RelatedParticipantsFinder $relatedParticipantsFinder)
    {
        $this->doctrine                  = $doctrine;
        $this->relatedParticipantsFinder = $relatedParticipantsFinder;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
             ->setDescription('Calculate all related participant for all events')
             ->addOption(
                 'all', 'a', InputOption::VALUE_NONE, 'If enabled, consider all events, including inactive ones'
             );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $all = $input->getOption('all');

        $repository = $this->doctrine->getRepository(Event::class);
        if ($all) {
            $events = $repository->findAll();
        } else {
            $events = $repository->findBy(['isActive' => true]);
        }

        $output->writeln(
            sprintf("\nGoing to calculate for <info>%d</info> events...", count($events))
        );

        $progress = new ProgressBar($output, count($events));
        $progress->start();

        $time = microtime(true);
        foreach ($events as $event) {
            $this->relatedParticipantsFinder->calculateProposedParticipantsForEvent($event);
            $progress->advance();
        }
        $progress->finish();
        $duration = round((microtime(true) - $time) * 1000);

        $output->writeln(
            sprintf("\nCalculated for <info>%d</info> events within <info>%d</info> ms", count($events), $duration)
        );

        return 0;
    }
}
