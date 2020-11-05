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


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class DataCommandBase extends ContainerAwareCommand
{
    
    /**
     * Path to output file
     *
     * @var string
     */
    protected $path;
    
    /**
     * If true, indicates that app-disable flag should be removed after execution
     *
     * @var bool
     */
    private $removeServiceFlag = false;
    
    /**
     * Create mysql configuration file
     */
    public function createMysqlConfigurationFile()
    {
        $container = $this->getContainer();
        
        $configurationPath = $container->getParameter('app.database.configuration.path');
        if (file_exists($configurationPath)) {
            unlink($configurationPath);
        }
        file_put_contents(
            $configurationPath, "[client]
user=" . $container->getParameter('database_user') . "
password=" . $container->getParameter('database_password')
        );
    }
    
    /**
     * Create file listing of transmitted path (files starting with dot excluded), provide relative paths as values
     *
     * @param string $basePath      Path to scan
     * @param string $subPathPrefix Prefix for relative paths
     * @return array|string[]
     */
    public static function createFileListing(string $basePath, string $subPathPrefix = ''): array
    {
        $files = [];
        
        /** @var \SplFileInfo $item */
        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            $path = $basePath . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if (!$item->isDir() && strpos($item->getFilename(), '.') !== 0) {
                $files[$path] = $subPathPrefix . $iterator->getSubPathName();
            }
        }
        return $files;
    }
    
    /**
     * Disable the service
     */
    protected function disableService()
    {
        $this->removeServiceFlag = !file_exists($this->getServiceDisablePath());
        touch($this->getServiceDisablePath());
    }
    
    /**
     * Ennable the service if disabled by this command
     */
    protected function enableService()
    {
        if ($this->removeServiceFlag) {
            if (file_exists($this->getServiceDisablePath())) {
                unlink($this->getServiceDisablePath());
            }
        }
    }
    
    /**
     * Get path to disable marker
     *
     * @return string
     */
    private function getServiceDisablePath(): string
    {
        return $this->getContainer()->getParameter('kernel.root_dir') . '/../web/app-disabled';
    }
    
}