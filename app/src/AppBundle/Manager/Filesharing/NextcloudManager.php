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
     * Provide unused name for team directory
     *
     * @param string $title
     * @param \DateTime $date
     * @return string
     */
    private function provideNameDirectoryTeam(string $title, \DateTime $date): string
    {
        $directories = $this->webDavConnector->listEventDirectories();
        
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
     * Create an event share and return chosen names of directories and groups
     *
     * @param string $title
     * @param \DateTime $date
     * @return EventShare
     */
    public function createEventShare(
        string $title, \DateTime $date
    ): EventShare
    {
        $start                   = microtime(true);
        $nameDirectoryTeam       = $this->provideNameDirectoryTeam($title, $date);
        $managementSuffix        = ' - ' . $this->managementLabel;
        $nameDirectoryManagement = self::sanitizeFileName($nameDirectoryTeam, 125 - mb_strlen($managementSuffix)) .
                                   $managementSuffix;
        
        $this->ocsConnector->createGroup($nameDirectoryTeam);
        $this->ocsConnector->addAdminToGroup($nameDirectoryTeam);
        $this->ocsConnector->promoteAdminToGroupAdmin($nameDirectoryTeam);
        $directoryTeam = $this->webDavConnector->createEventDirectory($nameDirectoryTeam);
        $this->ocsShareConnector->createShare($directoryTeam, $nameDirectoryTeam);
        
        $this->ocsConnector->createGroup($nameDirectoryManagement);
        $this->ocsConnector->addAdminToGroup($nameDirectoryManagement);
        $this->ocsConnector->promoteAdminToGroupAdmin($nameDirectoryManagement);
        $directoryManagement = $this->webDavConnector->createEventDirectory($nameDirectoryManagement);
        $this->ocsShareConnector->createShare($directoryManagement, $nameDirectoryTeam);
        
        $duration = round(microtime(true) - $start);
        $this->logger->info(
            'Created {name} within {duration} s', ['name' => $nameDirectoryTeam, 'duration' => $duration]
        );
        
        return new EventShare(
            $nameDirectoryTeam, $directoryTeam->getFileId(), $nameDirectoryManagement, $directoryManagement->getFileId()
        );
    }

    /**
     * Ensure that only expected users are assigned to related groups
     *
     * @param string $nameTeam
     * @param array  $usersTeam
     * @param string $nameManagement
     * @param array  $usersManagement
     */
    public function updateEventShareAssignments(
        string $nameTeam,
        array $usersTeam,
        string $nameManagement,
        array $usersManagement
    ) {
        $start = microtime(true);
        $this->ensureGroupHasOnlyTransmittedUsersAssigned($nameTeam, $usersTeam);
        $this->ensureGroupHasOnlyTransmittedUsersAssigned($nameManagement, $usersManagement);
        $duration = round(microtime(true) - $start);
        $this->logger->info(
            'Updated user assignments for {nameTeam}, {nameManagement} within {duration} s',
            ['nameTeam' => $nameTeam, 'nameManagement' => $nameManagement, 'duration' => $duration]
        );
    }

    /**
     * Ensure that only transmitted users are assigned to transmitted group, plus admin user
     *
     * @param string   $group         Group to set
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
