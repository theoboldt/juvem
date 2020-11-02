<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Move customization twig templates to new config directory
 */
final class Version20201102100000 extends AbstractMigration
{

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Move customization twig templates to new config directory';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema): void
    {
        $targetPath = __DIR__ . '/../../config/templates';
        $sourcePath = __DIR__ . '/../config';

        umask(0);
        if (!file_exists($targetPath)) {
            if (mkdir($targetPath, 0777, true)) {
                throw new \RuntimeException('Failed to create ' . $targetPath);
            }
        }
        $this->moveFileIfExists($sourcePath, $targetPath, 'imprint-content.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-of-travel-content.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-of-travel-scrollspy.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-corona-content.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-corona-scrollspy.html.twig');
    }

    /**
     * {@inheritDoc}
     */
    public function down(Schema $schema): void
    {
        $targetPath = __DIR__ . '/../config';
        $sourcePath = __DIR__ . '/../../config/templates';
        umask(0);
        if (!file_exists($targetPath)) {
            if (mkdir($targetPath, 0777, true)) {
                throw new \RuntimeException('Failed to create ' . $targetPath);
            }
        }

        $this->moveFileIfExists($sourcePath, $targetPath, 'imprint-content.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-of-travel-content.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-of-travel-scrollspy.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-corona-content.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-corona-scrollspy.html.twig');
    }

    /**
     * Move file
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @param string $fileName
     */
    private function moveFileIfExists(string $sourcePath, string $targetPath, string $fileName): void
    {
        if (file_exists($sourcePath . '/' . $fileName)) {
           $this->addSql('-- "Moved '.$fileName.'"');
            if (!rename($sourcePath . '/' . $fileName, $targetPath . '/' . $fileName)) {
                throw new \RuntimeException(
                    sprintf('Failed to move file "%s" from "%s" to "%s"', $fileName, $sourcePath, $targetPath)
                );
            }
        } else {
           $this->addSql('-- "No need to move '.$fileName.'"');
        }
    }
}
