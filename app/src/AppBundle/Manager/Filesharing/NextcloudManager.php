<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Filesharing;


use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class NextcloudManager
{
    /**
     * @var NextcloudOcsConnector
     */
    private NextcloudOcsConnector $ocsConnector;

    /**
     * @var NextcloudWebDavConnector
     */
    private NextcloudWebDavConnector $webDavConnector;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * NextcloudManager constructor.
     *
     * @param string               $baseUri
     * @param string               $username
     * @param string               $password
     * @param string               $folder
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        string $baseUri,
        string $username,
        string $password,
        string $folder,
        ?LoggerInterface $logger = null
    ) {
        $connectionConfiguration = new NextcloudConnectionConfiguration(
            $baseUri, $username, $password, $folder
        );
        $this->logger            = $logger ?? new NullLogger();
        $this->ocsConnector      = new NextcloudOcsConnector($connectionConfiguration, $this->logger);
        $this->webDavConnector   = new NextcloudWebDavConnector($connectionConfiguration, $this->logger);
    }


    /**
     * Create instance if configuration is not empty
     *
     * @param string               $baseUri
     * @param string               $username
     * @param string               $password
     * @param string               $folder
     * @param LoggerInterface|null $logger
     * @return NextcloudManager|null
     */
    public static function create(
        string $baseUri = '',
        string $username = '',
        string $password = '',
        string $folder = '',
        ?LoggerInterface $logger = null
    ): ?NextcloudManager {
        $baseUri  = trim($baseUri);
        $username = trim($username);
        $folder   = trim($folder);
        $folder   = trim($folder, '/\\');
        if (empty($baseUri) || empty($username)) {
            return null;
        }
        return new self($baseUri, $username, $password, $folder, $logger);
    }

    public function listEventDirectories()
    {
        $this->webDavConnector->listEventDirectories();
    }

}
