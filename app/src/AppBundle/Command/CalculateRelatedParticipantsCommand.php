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
use AppBundle\Entity\Participant;
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

        $this->fixupParticipantsModifiedAtByAuditLog($output);

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

    /**
     * @param OutputInterface $output
     * @return void
     */
    protected function fixupParticipantsModifiedAtByAuditLog(OutputInterface $output){

        /** ## FIXUP modifiedAt dates for participants */
        $sql  = 'SELECT aid, modified_at
                     FROM participant
                     WHERE YEAR(modified_at) > 2023';
        $participantList = $this->provideAidModifiedAtList($sql);
        $sql  = 'SELECT related_id, MAX(occurrence_date)
                     FROM entity_change
                    WHERE related_class = "AppBundle\\\\Entity\\\\Participant"
                 GROUP BY related_id';
        $changeOccurrenceList = $this->provideAidModifiedAtList($sql);

        $output->writeln(
            sprintf("\nGoing to fixup modified dates for <info>%d</info> participants...", count($participantList))
        );

        $progress = new ProgressBar($output, count($participantList));
        $progress->start();

        $time = microtime(true);
        $fixed = 0;
        foreach ($participantList as $aid => $participantModifiedAt) {
            if (isset($changeOccurrenceList[$aid]) && $changeOccurrenceList[$aid] < $participantModifiedAt) {
                $this->doctrine->getConnection()->executeQuery(
                    'UPDATE participant SET modified_at = ? WHERE aid = ?',
                    [$changeOccurrenceList[$aid]->format('Y-m-d H:i:s'), $aid]
                );
                ++$fixed;
            }
            $progress->advance();
        }

        $progress->finish();
        $duration = round((microtime(true) - $time) * 1000);

        $output->writeln(
            sprintf("\nFixed modified date for <info>%d</info> participants within <info>%d</info> ms", $fixed, $duration)
        );
    }

    /**
     * Retrieves a list of aid IDs and their corresponding modification dates, converting the dates to DateTimeImmutable objects.
     *
     * @param string $sql The SQL query to execute in order to fetch the aid IDs and their modification dates.
     * @return array An associative array where the keys are aid IDs and the values are DateTimeImmutable objects representing the modification dates.
     */
    function provideAidModifiedAtList(string $sql): array
    {
        $modifiedAt = null;
        $aidModifiedAt = $this->doctrine->getConnection()->executeQuery(
            $sql, []
        )->fetchAllKeyValue();
        foreach ($aidModifiedAt as $aid => &$modifiedAt) {
            $modifiedAt = new \DateTimeImmutable($modifiedAt);
        }
        unset($modifiedAt);
        return $aidModifiedAt;
    }
}
