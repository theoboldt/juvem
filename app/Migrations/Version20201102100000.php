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
        return 'Move customization twig templates, tmp dir to new config directory';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema): void
    {
        $targetPath = __DIR__ . '/../../var/config/templates';
        $sourcePath = __DIR__ . '/../config';

        umask(0);
        if (!file_exists($targetPath)) {
            if (!mkdir($targetPath, 0777, true)) {
                throw new \RuntimeException('Failed to create ' . $targetPath);
            }
        }
        $this->moveFileIfExists(__DIR__ . '/../config', __DIR__ . '/../../config', 'branding.scss');
        $this->moveFileIfExists(__DIR__ . '/../config', __DIR__ . '/../../config', 'parameters.yml');
        $this->moveFileIfExists($sourcePath, $targetPath, 'imprint-content.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-of-travel-content.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-of-travel-scrollspy.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-corona-content.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-corona-scrollspy.html.twig');

        $this->moveFileIfExists(__DIR__ . '/../..', __DIR__ . '/../../var', 'tmp');
        $this->moveFileIfExists(__DIR__ . '/../../data', __DIR__ . '/../../var/data', 'invoice');
        $this->moveFileIfExists(__DIR__ . '/../../data', __DIR__ . '/../../var/data', 'template.docx');
        $this->moveFileIfExists(__DIR__ . '/../../data', __DIR__ . '/../../var/data', 'uploads');
    }

    /**
     * {@inheritDoc}
     */
    public function down(Schema $schema): void
    {
        $targetPath = __DIR__ . '/../var/config';
        $sourcePath = __DIR__ . '/../../config/templates';
        umask(0);
        if (!file_exists($targetPath)) {
            if (!mkdir($targetPath, 0777, true)) {
                throw new \RuntimeException('Failed to create ' . $targetPath);
            }
        }

        $this->moveFileIfExists(__DIR__ . '/../../config', __DIR__ . '/../config', 'branding.scss');
        $this->moveFileIfExists(__DIR__ . '/../../config', __DIR__ . '/../config', 'parameters.yml');
        $this->moveFileIfExists($sourcePath, $targetPath, 'imprint-content.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-of-travel-content.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-of-travel-scrollspy.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-corona-content.html.twig');
        $this->moveFileIfExists($sourcePath, $targetPath, 'conditions-corona-scrollspy.html.twig');

        $this->moveFileIfExists(__DIR__ . '/../../var', __DIR__ . '/../..', 'tmp');
        $this->moveFileIfExists(__DIR__ . '/../../var/data', __DIR__ . '/../../data', 'invoice');
        $this->moveFileIfExists(__DIR__ . '/../../var/data', __DIR__ . '/../../data', 'template.docx');
        $this->moveFileIfExists(__DIR__ . '/../../var/data', __DIR__ . '/../../data', 'uploads');
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
            if (!file_exists($targetPath)) {
                if (!mkdir($targetPath, 0777, true)) {
                    throw new \RuntimeException('Failed to create ' . $targetPath);
                }
            }

            if (file_exists($targetPath . '/' . $fileName)) {
                throw new \RuntimeException(
                    sprintf('Cannot move file "%s" from "%s" to "%s", target already existing', $fileName, $sourcePath, $targetPath)
                );
            }
     
           $this->addSql('-- "Moved '.$fileName.'"');
           exec('mv ' . $sourcePath . '/' . $fileName . ' ' . $targetPath . '/' . $fileName, $output, $return);
            if ($return !== 0) {
                throw new \RuntimeException(
                    sprintf('Failed to move file "%s" from "%s" to "%s"; Output %s', $fileName, $sourcePath, $targetPath, implode(', ', $output))
                );
            }
        } else {
           $this->addSql('-- "No need to move '.$fileName.'"');
        }
    }
}
