<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Command\Data;


use Symfony\Component\Console\Command\Command;

abstract class DataCommandBase extends Command
{
    
    /**
     * database_user
     *
     * @var string
     */
    protected string $databaseUser;
    
    /**
     * database_password
     *
     * @var string
     */
    protected string $databasePassword;
    
    /**
     * database_host
     *
     * @var string
     */
    protected string $databaseHost;
    
    /**
     * database_port
     *
     * @var string|int
     */
    protected  $databasePort;
    
    /**
     * database_name
     *
     * @var string
     */
    protected  string $databaseName;
    
    /**
     * app.database.configuration.path
     *
     * @var string
     */
    protected string $databaseConfigFilePath;
    
    /**
     * app.web.root.path
     *
     * @var string
     */
    protected string $webRootPath;
    
    /**
     * app.tmp.root.path
     *
     * @var string
     */
    protected string $tmpRootPath;
    
    /**
     * app.data.root.path
     *
     * @var string
     */
    protected string $dataRootPath;
    
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
    private bool $removeServiceFlag = false;
    
    /**
     * DataCommandBase constructor.
     *
     * @param string $databaseUser
     * @param string $databasePassword
     * @param string $databaseHost
     * @param string|int $databasePort
     * @param string $databaseName
     * @param string $databaseConfigFilePath
     * @param string $webRootPath
     * @param string $tmpRootPath
     * @param string $dataRootPath
     */
    public function __construct(
        string $databaseUser,
        string $databasePassword,
        string $databaseHost,
        $databasePort,
        string $databaseName,
        string $databaseConfigFilePath,
        string $webRootPath,
        string $tmpRootPath,
        string $dataRootPath
    )
    {
        $this->databaseUser           = $databaseUser;
        $this->databasePassword       = $databasePassword;
        $this->databaseHost           = $databaseHost;
        $this->databasePort           = $databasePort;
        $this->databaseName           = $databaseName;
        $this->databaseConfigFilePath = $databaseConfigFilePath;
        $this->webRootPath            = $webRootPath;
        $this->tmpRootPath            = $tmpRootPath;
        $this->dataRootPath           = $dataRootPath;
        parent::__construct();
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
        
        if (!file_exists($basePath)) {
            return $files;
        }
        
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
        return $this->webRootPath . '/app-disabled';
    }
    
}