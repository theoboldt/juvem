<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Move uploads directory into data folder
 */
final class Version20190219170000 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    public function getDescription()
    {
        return 'Move uploads directory into data folder';
    }
    
    public function up(Schema $schema): void
    {
        $oldExists = file_exists($this->getOldPath());
        $this->skipIf(!$oldExists, 'Old data directory does not exist');
        $this->addSql('SELECT 1');
        if (!$oldExists) {
            return;
        }
        
        if ($this->copy($this->getOldPath(), $this->getNewPath())) {
            $this->remove($this->getOldPath());
        } else {
            $this->write('Failed to copy all files');
        }
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('SELECT 1');
        
        if ($this->copy($this->getNewPath(), $this->getOldPath())) {
            $this->remove($this->getNewPath());
        } else {
            $this->write('Failed to copy all files');
        }
    }
    
    /**
     * Get path to old uploads dir
     *
     * @return string
     */
    private function getOldPath()
    {
        return rtrim($this->container->getParameter('kernel.root_dir'), '/') . '/../uploads';
    }
    
    /**
     * Get path to new uploads dir
     *
     * @return string
     */
    private function getNewPath()
    {
        return rtrim($this->container->getParameter('kernel.root_dir'), '/') . '/../data/uploads';
    }
    
    
    /**
     * Copy files recursively
     *
     * @param string $source
     * @param string $dest
     * @return bool
     */
    private function copy(string $source, string $dest): bool
    {
        $success = true;
        $umask   = umask(0);
        if (!file_exists($dest)) {
            if (!mkdir($dest, 0777, true)) {
                throw new \RuntimeException('Failed to make ' . $dest);
            }
        }
        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            $path = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                
                if (!file_exists($path) && !mkdir($path)) {
                    $success = false;
                    $this->container->get('logger')->error('Failed to create directory {path}', ['path' => $path]);
                }
            } else {
                if (!copy($item->getPathname(), $path)) {
                    $success = false;
                    $this->container->get('logger')->error(
                        'Failed to copy file {source} to {target}', ['source' => $item, 'target' => $path]
                    );
                    
                }
            }
        }
        umask($umask);
        return $success;
    }
    
    /**
     * Remove dir recursively
     *
     * @param $dir
     */
    private function remove($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    $path = $dir . "/" . $object;
                    
                    if (is_dir($path)) {
                        $this->remove($path);
                    } else {
                        if (!unlink($path)) {
                            $this->container->get('logger')->error('Failed to delete file {path}', ['path' => $path]);
                        }
                    }
                }
            }
            if (!rmdir($dir)) {
                $this->container->get('logger')->error('Failed to delete directory {path}', ['path' => $dir]);
            }
        }
    }
}
