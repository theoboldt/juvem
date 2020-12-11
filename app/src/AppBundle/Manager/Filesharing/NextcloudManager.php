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


use AppBundle\Manager\Filesharing\OcsApi\NextcloudOcsConnector;
use AppBundle\Manager\Filesharing\OcsShareApi\NextcloudOcsShareConnector;
use AppBundle\Manager\Filesharing\WebDavApi\NextcloudWebDavConnector;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class NextcloudManager
{
    /**
     * @var string
     */
    private string $managementLabel;
    
    /**
     * @var NextcloudConnectionConfiguration
     */
    protected NextcloudConnectionConfiguration $configuration;
    
    /**
     * @var NextcloudOcsConnector
     */
    private NextcloudOcsConnector $ocsConnector;
    
    /**
     * @var NextcloudOcsShareConnector
     */
    private NextcloudOcsShareConnector $ocsShareConnector;
    
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
     * @param string $baseUri
     * @param string $username
     * @param string $password
     * @param string $folder
     * @param string $managementLabel
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        string $baseUri,
        string $username,
        string $password,
        string $folder,
        string $managementLabel,
        ?LoggerInterface $logger = null
    )
    {
        $this->configuration     = new NextcloudConnectionConfiguration(
            $baseUri, $username, $password, $folder
        );
        $this->managementLabel   = $managementLabel;
        $this->logger            = $logger ?? new NullLogger();
        $this->ocsConnector      = new NextcloudOcsConnector($this->configuration, $this->logger);
        $this->ocsShareConnector = new NextcloudOcsShareConnector($this->configuration, $this->logger);
        $this->webDavConnector   = new NextcloudWebDavConnector($this->configuration, $this->logger);
    }
    
    /**
     * Create instance if configuration is not empty
     *
     * @param null|string $baseUri
     * @param null|string $username
     * @param null|string $password
     * @param null|string $folder
     * @param string|null $managementLabel
     * @param LoggerInterface|null $logger
     * @return NextcloudManager|null
     */
    public static function create(
        ?string $baseUri = '',
        ?string $username = '',
        ?string $password = '',
        ?string $folder = '',
        ?string $managementLabel = '',
        ?LoggerInterface $logger = null
    ): ?NextcloudManager
    {
        $baseUri         = trim($baseUri);
        $username        = trim($username);
        $folder          = trim(str_replace(['/', '\\'], '_', $folder));
        $managementLabel = trim(str_replace(['/', '\\'], '_', $managementLabel));
        if (empty($baseUri) || empty($username)) {
            return null;
        }
        return new self($baseUri, $username, $password, $folder, $managementLabel, $logger);
    }
    
    /**
     * Check if directory name is already in list
     *
     * @param array $directories
     * @param string $name
     * @return bool
     */
    private static function isDirectoryNameExisting(array $directories, string $name): bool
    {
        /** @var NextcloudDirectory $directory */
        foreach ($directories as $directory) {
            if ($directory->getName() === $name) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Create a new unique event root directory on file share
     *
     * @param string $title
     * @param \DateTime $date
     * @return NextcloudDirectory
     */
    public function createUniqueEventRootDirectory(string $title, \DateTime $date): NextcloudDirectory
    {
        $start         = microtime(true);
        $nameDirectory = $this->provideUniqueEventRootName($title, $date);
        
        $directory = $this->webDavConnector->createEventRootDirectory($nameDirectory);
        
        $duration = round((microtime(true) - $start) * 1000);
        $this->logger->info(
            'Created event root directory {name} within {duration} ms',
            ['name' => $nameDirectory, 'duration' => $duration]
        );
        return $directory;
    }
    
    /**
     * Provide unused name for event root directory
     *
     * @param string $title
     * @param \DateTime $date
     * @return string
     */
    private function provideUniqueEventRootName(string $title, \DateTime $date): string
    {
        $directories = $this->webDavConnector->listEventRootDirectories();
        
        $nameDirectoryTeam = self::sanitizeFileName($title);
        if (self::isDirectoryNameExisting($directories, $nameDirectoryTeam)) {
            $nameDirectoryTeam = self::sanitizeFileName($title . ' ' . $date->format('Y'));
        }
        if (self::isDirectoryNameExisting($directories, $nameDirectoryTeam)) {
            $nameDirectoryTeam = self::sanitizeFileName($title . ' ' . $date->format('Y-m-d'));
        }
        if (self::isDirectoryNameExisting($directories, $nameDirectoryTeam)) {
            $i = 0;
            do {
                $nameDirectoryTeam = self::sanitizeFileName($title . ' ' . $date->format('Y-m-d') . ' ' . $i++);
            } while (self::isDirectoryNameExisting($directories, $nameDirectoryTeam));
        }
        if (empty($nameDirectoryTeam)) {
            throw new \InvalidArgumentException('Failed to generate name for ' . $title);
        }
        
        return $nameDirectoryTeam;
    }
    
    /**
     * Create team share for event
     *
     * @param NextcloudDirectory $eventRootDirectory
     * @return NextcloudDirectory
     */
    public function createEventTeamShare(NextcloudDirectory $eventRootDirectory): NextcloudDirectory
    {
        return $this->createEventShare($eventRootDirectory, $eventRootDirectory->getName());
    }
    
    /**
     * Create team share for event
     *
     * @param NextcloudDirectory $eventRootDirectory
     * @return NextcloudDirectory
     */
    public function createEventManagementShare(NextcloudDirectory $eventRootDirectory): NextcloudDirectory
    {
        $managementSuffix        = ' - ' . $this->managementLabel;
        $nameDirectoryManagement = self::sanitizeFileName(
                $eventRootDirectory->getName(), 125 - mb_strlen($managementSuffix)
            ) . $managementSuffix;
        
        return $this->createEventShare($eventRootDirectory, $nameDirectoryManagement);
    }
    
    /**
     * Create an event sub directory, create groups and share
     *
     * @param NextcloudDirectory $eventRootDirectory Event root directory
     * @param string $title                          Sub directory and group title
     * @return NextcloudDirectory
     */
    private function createEventShare(
        NextcloudDirectory $eventRootDirectory,
        string $title
    ): NextcloudDirectory
    {
        $start = microtime(true);
        
        $directory = $this->webDavConnector->createSubDirectory($eventRootDirectory, $title);
        $this->ocsConnector->createGroup($title);
        $this->ocsConnector->addAdminToGroup($title);
        $this->ocsConnector->promoteAdminToGroupAdmin($title);
        $this->ocsShareConnector->createShare($directory, $title);
        
        $duration = round((microtime(true) - $start)*1000);
        $this->logger->info(
            'Created {name} within {duration} ms', ['name' => $title, 'duration' => $duration]
        );
        
        return $directory;
    }
    
    /**
     * Ensure that only expected users are assigned to related groups
     *
     * @param string $name
     * @param array $users
     */
    public function updateEventShareAssignments(
        string $name,
        array $users
    ) {
        $start = microtime(true);
        $this->ensureGroupHasOnlyTransmittedUsersAssigned($name, $users);
        $duration = round((microtime(true) - $start) * 1000);
        $this->logger->info(
            'Updated user assignments for {name} within {duration} ms',
            ['name' => $name, 'duration' => $duration]
        );
    }
    
    /**
     * Ensure that only transmitted users are assigned to transmitted group, plus admin user
     *
     * @param string $group           Group to set
     * @param string[] $expectedUsers List of users
     */
    private function ensureGroupHasOnlyTransmittedUsersAssigned(string $group, array $expectedUsers): void
    {
        $expectedUsers[] = $this->configuration->getUsername();
        $expectedUsers   = array_unique($expectedUsers);
        
        $currentUsers = $this->ocsConnector->fetchUsersOfGroup($group);
        foreach ($currentUsers as $currentUser) {
            if (!in_array($currentUser, $expectedUsers)) {
                $this->ocsConnector->removeUserFromGroup($currentUser, $group);
            }
        }
        
        foreach ($expectedUsers as $expectedUser) {
            if (!in_array($expectedUser, $currentUsers)) {
                $this->ocsConnector->addUserToGroup($expectedUser, $group);
            }
        }
    }
    
    /**
     * Sanitize a filename, but keep some special characters which are widely supported
     *
     * @param string $input
     * @param int $maxLength Max length of allowed string
     * @return string
     */
    public static function sanitizeFileName(string $input, int $maxLength = 125): string
    {
        $output = $input;
        if ((!preg_match('/^[\x20-\x7e]*$/', $input) || false !== strpos($input, '%'))) {
            $encoding = mb_detect_encoding($input, null, true) ?: '8bit';
            $output   = '';
            
            for ($i = 0, $inputLength = mb_strlen($input, $encoding); $i < $inputLength; ++$i) {
                $char = mb_substr($input, $i, 1, $encoding);
                
                $ordChar = \ord($char);
                
                if ($char === '&') {
                    $output .= 'und';
                } elseif (in_array(mb_strtolower($char), ['ä', 'ö', 'ü', ' ', '_', '-', '(', ')'])
                          || ($ordChar >= 48 && $ordChar <= 57)
                          || ($ordChar >= 65 && $ordChar <= 90)
                          || ($ordChar >= 97 && $ordChar <= 122)
                ) {
                    $output .= $char;
                } elseif (!in_array($char, ['?', '!'])) {
                    $output .= '_';
                }
            }
        }
        
        if (mb_strlen($output) >= $maxLength) {
            return mb_substr($output, 0, $maxLength) . '...';
        } else {
            return $output;
        }
    }
    
}
