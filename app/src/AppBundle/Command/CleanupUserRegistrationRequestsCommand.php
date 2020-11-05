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
use AppBundle\Entity\GalleryImage;
use AppBundle\Entity\User;
use Imagine\Image\ImageInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupUserRegistrationRequestsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('app:user:cleanup')
             ->setDescription('Remove unconfirmed old user registration requests')
             ->addOption('dry-run', 'd', InputOption::VALUE_NONE)
             ->addArgument(
                 'age', InputArgument::OPTIONAL, 'Maximum allowed age of unconfirmed registration requests in days', 30
             );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $maxAge = $input->getArgument('age');
        $dry    = $input->getOption('dry-run');

        $repository = $this->getContainer()->get('doctrine')->getRepository(User::class);
        $users      = $repository->findUnconfirmed($maxAge);

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('Affected users:');
            if (count($users)) {
                $table = new Table($output);
                $table->setHeaders(['First name', 'Last name', 'Created']);
                /** @var User $user */
                foreach ($users as $user) {
                    $table->addRow(
                        [$user->getNameFirst(), $user->getNameLast(), $user->getCreatedAt()->format('Y-m-d')]
                    );
                }
                $table->render();
            } else {
            $output->writeln('(none)');
            }
        }

        if ($dry) {
            $output->writeln('No changes applied');
        } else {
            $output->write('Deleting users...');
            $em = $this->getContainer()->get('doctrine')->getManager();
            foreach ($users as $user) {
                $em->remove($user);
            }
            $em->flush();
            $output->writeln(' done.');
        }
    }
}
