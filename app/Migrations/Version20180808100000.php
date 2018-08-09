<?php declare(strict_types=1);

namespace Application\Migrations;

use AppBundle\Entity\GalleryImage;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180808100000 extends AbstractMigration implements ContainerAwareInterface {
    use ContainerAwareTrait;
    
    public function up(Schema $schema) {
        $this->addSql('SELECT 1');
        $this->connection->exec(
            'ALTER TABLE gallery_image ADD width INT UNSIGNED NOT NULL, ADD height INT UNSIGNED NOT NULL'
        );
        
        $manager    = $this->container->get('app.gallery_image_manager');
        $repository = $this->container->get('doctrine')->getRepository(GalleryImage::class);
        $em         = $this->container->get('doctrine.orm.entity_manager');
        $images     = $repository->findAll();
        
        $updated = 0;
        $deleted = 0;
        
        /** @var GalleryImage $image */
        foreach ($images as $image) {
            $path = $manager->getOriginalImagePath($image->getFilename());
            if (file_exists($path)) {
                list($width, $height) = getimagesize($path);
                $image->setWidth($width);
                $image->setHeight($height);
                $em->persist($image);
                ++$updated;
            } else {
                $em->remove($image);
                ++$deleted;
            }
        }
        $em->flush();
        
        $this->write(sprintf('Updated %d images, removed %d orphaned', $updated, $deleted));
    }
    
    public function down(Schema $schema) {
        $this->addSql('ALTER TABLE gallery_image DROP width, DROP height');
    }
}
