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
use AppBundle\Manager\UploadImageManager;
use AppBundle\Manager\UploadImageManager\AbstractFileException;
use Doctrine\Persistence\ManagerRegistry;
use Imagine\Image\ImageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EventImageGalleryGenerateCommand extends Command
{
    /**
     * doctrine
     *
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;
    
    /**
     * app.gallery_image_manager
     *
     * @var UploadImageManager
     */
    private UploadImageManager $galleryImageManager;
    
    /**
     * EventImageGalleryGenerateCommand constructor.
     *
     * @param ManagerRegistry $doctrine
     * @param UploadImageManager $galleryImageManager
     */
    public function __construct(ManagerRegistry $doctrine, UploadImageManager $galleryImageManager)
    {
        $this->doctrine       = $doctrine;
        $this->galleryImageManager = $galleryImageManager;
        parent::__construct();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('app:event:gallery')
             ->setDescription('Ensure gallery images are cached')
             ->addOption('dry-run', 'd', InputOption::VALUE_NONE)
             ->addArgument('event', InputArgument::REQUIRED, 'Event ID of which images should be cached')
             ->addArgument('memory', InputArgument::OPTIONAL, 'Memory limit override in M');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $memoryLimit = null;
        if ($input->hasArgument('memory')) {
            $memoryLimit = $input->getArgument('memory');
        }
        if (!is_numeric($memoryLimit) || $memoryLimit < 256) {
            $memoryLimit = 512;
        }
        \ini_set('memory_limit', $memoryLimit . 'M');

        $dry = $input->getOption('dry-run');

        $eventRepository = $this->doctrine->getRepository(Event::class);
        /** @var Event $event */
        $event = $eventRepository->find($input->getArgument('event'));
        if (!$event) {
            throw new \RuntimeException('Did not find event with transmitted id');
        }
        $imageRepository = $this->doctrine->getRepository(GalleryImage::class);
        $images          = $imageRepository->findByEvent($event);

        $this->executeResize($output, $images, $dry);
        return 0;
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
        $uploadManager = $this->galleryImageManager;

        $progress = new ProgressBar($output, count($images));
        $progress->start();
        $prepared = 0;
        
        /** @var GalleryImage $image */
        foreach ($images as $image) {
            if (!$dry) {
                try {
                    $uploadManager->fetchResized(
                        $image->getFilename(), GalleryImage::THUMBNAIL_DIMENSION, GalleryImage::THUMBNAIL_DIMENSION,
                        ImageInterface::THUMBNAIL_OUTBOUND, 30
                    );
                    $uploadManager->fetchResized(
                        $image->getFilename(), GalleryImage::THUMBNAIL_DETAIL, GalleryImage::THUMBNAIL_DETAIL,
                        ImageInterface::THUMBNAIL_INSET, 70
                    );
                    ++$prepared;
                } catch (AbstractFileException $e) {
                    $output->writeln(sprintf("<error>%s</error>", $e->getMessage()));
                }

            }
            $progress->advance();
        }
        $progress->finish();
        $output->writeln(sprintf("\n      Prepared <info>%d</info> images", $prepared));
    }
}
