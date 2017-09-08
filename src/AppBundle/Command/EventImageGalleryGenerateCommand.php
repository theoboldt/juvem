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
use AppBundle\Entity\Task;
use Imagine\Image\ImageInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EventImageGalleryGenerateCommand extends ContainerAwareCommand
{
    /**
     * Event Task
     *
     * @var Task
     */
    protected $task;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('app:event:gallery')
             ->setDescription('Send event subscription emails')
             ->addOption('dry-run', 'd', InputOption::VALUE_NONE)
             ->addArgument('event', InputArgument::REQUIRED, 'Event ID of which images should be cached');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dry  = $input->getOption('dry-run');
        $run  = new \DateTime();

        $eventRepository = $this->getContainer()->get('doctrine')->getRepository(Event::class);
        /** @var Event $event */
        $event           = $eventRepository->find($input->getArgument('event'));
        if (!$event) {
            throw new \RuntimeException('Did not find event with transmitted id');
        }
        $imageRepository = $this->getContainer()->get('doctrine')->getRepository(GalleryImage::class);
        $images          = $imageRepository->findByEvent($event);

        $this->executeResize($output, $images, $dry);
    }

    /**
     * Executes the participant parse
     *
     * @param OutputInterface      $output An OutputInterface instance
     * @param array|GalleryImage[] $images Set of images to resize
     * @param bool                 $dry    Set to true to not send emails
     */
    protected function executeResize(OutputInterface $output, array $images, $dry = false)
    {
        $uploadManager = $this->getContainer()->get('app.gallery_image_manager');

        $progress = new ProgressBar($output, count($images));
        $progress->start();
        /** @var GalleryImage $image */
        foreach ($images as $image) {
            if (!$dry) {
                $uploadManager->fetchResized(
                    $image->getFilename(), GalleryImage::THUMBNAIL_DIMENSION, GalleryImage::THUMBNAIL_DIMENSION,
                    ImageInterface::THUMBNAIL_OUTBOUND, 30
                );
                $uploadManager->fetchResized(
                    $image->getFilename(), GalleryImage::THUMBNAIL_DETAIL, GalleryImage::THUMBNAIL_DETAIL,
                    ImageInterface::THUMBNAIL_INSET, 70
                );

            }
            $progress->advance();
        }
        $progress->finish();
        $output->writeln(sprintf("\n      Prepared <info>%d</info> images", count($images)));
    }
}
