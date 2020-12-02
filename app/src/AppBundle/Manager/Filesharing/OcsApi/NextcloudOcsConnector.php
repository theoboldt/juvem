<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Filesharing\OcsApi;


use AppBundle\Manager\Filesharing\AbstractNextcloudConnector;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class NextcloudOcsConnector extends AbstractNextcloudConnector
{
    const API_PATH = '/ocs/v1.php/';
    
    /**
     * Configures the Guzzle client for juvimg service
     *
     * @return Client
     */
    protected function client(): Client
    {
        if (!$this->client) {
            $this->client = new Client(
                [
                    'base_uri'                  => $this->configuration->getBaseUri(),
                    RequestOptions::AUTH        => [
                        $this->configuration->getUsername(), $this->configuration->getPassword(),
                    ],
                    RequestOptions::HTTP_ERRORS => false,
                    RequestOptions::COOKIES     => true,
                    RequestOptions::HEADERS     => [
                        'OCS-APIRequest' => 'true'
                    ]
                ]
            );
        }
        return $this->client;
    }
    
    /**
     * Do request
     *
     * @param string $method
     * @param string $subUri
     * @param array $options
     * @return ResponseInterface
     */
    private function request(string $method, string $subUri, array $options = []): ResponseInterface
    {
        try {
            $response = $this->client()->request(
                $method, self::API_PATH . $subUri, $options
            );
        } catch (\Exception $e) {
            throw new NextcloudOcsOperationFailedException(
                'Request failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
        return $response;
    }
    
    /**
     * Create new group
     *
     * @param string $name
     */
    public function createGroup(string $name): void
    {
        $start      = microtime(true);
        $response   = $this->request('POST', 'cloud/groups', ['form_params' => ['groupid' => $name]]);
        $xml        = self::extractXmlResponse($response);
        $statusCode = (int)self::extractXmlProperty($xml, '//ocs/meta/statuscode');
        $status     = self::extractXmlProperty($xml, '//ocs/meta/status');
        
        if ($statusCode === 102) {
            throw new NextcloudCreateGroupExistsException(
                sprintf('Failed to create group "%s", already exists', $name)
            );
        } elseif ($statusCode !== 100) {
            throw new NextcloudCreateGroupFailedException(
                sprintf('Failed to create group "%s", status: %s', $name, $status)
            );
        }
        
        $duration = round(microtime(true) - $start);
        $this->logger->debug(
            'Created nextcloud group {name} within {duration} s', ['name' => $name, 'duration' => $duration]
        );
    }
    
    /**
     * Add this admin user to transmitted group
     *
     * @param string $group
     */
    public function addAdminToGroup(string $group): void
    {
        $this->addUserToGroup($this->configuration->getUsername(), $group);
    }
    
    /**
     * Assign user to group
     *
     * @param string $username
     * @param string $group
     */
    public function addUserToGroup(string $username, string $group): void
    {
        $start      = microtime(true);
        $response   = $this->request(
            'POST',
            'cloud/users/' . urlencode($username) . '/groups',
            ['form_params' => ['groupid' => $group]]
        );
        $xml        = self::extractXmlResponse($response);
        $statusCode = (int)self::extractXmlProperty($xml, '//ocs/meta/statuscode');
        $status     = self::extractXmlProperty($xml, '//ocs/meta/status');
        
        if ($statusCode !== 100) {
            throw new NextcloudAssignUserToGroupFailedException(
                sprintf('Failed to assign "%s" to "%s", status: %s', $username, $group, $status)
            );
        }
        $duration = round(microtime(true) - $start);
        $this->logger->debug(
            'Added nextcloud user {username} to group {group} within {duration} s',
            ['username' => $username, 'group' => $group, 'duration' => $duration]
        );
    }
    
    /**
     * Make admin to subadmin of group
     *
     * @param string $group
     */
    public function promoteAdminToGroupAdmin(string $group): void
    {
        $this->promoteUserToGroupAdmin($this->configuration->getUsername(), $group);
    }
    
    /**
     * Make user to subadmin of group
     *
     * @param string $username
     * @param string $group
     */
    public function promoteUserToGroupAdmin(string $username, string $group): void
    {
        $start      = microtime(true);
        $response   = $this->request(
            'POST',
            'cloud/users/' . urlencode($username) . '/subadmins',
            ['form_params' => ['groupid' => $group]]
        );
        $xml        = self::extractXmlResponse($response);
        $statusCode = (int)self::extractXmlProperty($xml, '//ocs/meta/statuscode');
        $status     = self::extractXmlProperty($xml, '//ocs/meta/status');
        
        if ($statusCode !== 100) {
            throw new NextcloudPromoteUserToGroupAdminFailedException(
                sprintf('Failed to promote user "%s" for "%s", status: %s', $username, $group, $status)
            );
        }
        $duration = round(microtime(true) - $start);
        $this->logger->debug(
            'Promoted nextcloud user {username} to subadmin of {group} within {duration} s',
            ['username' => $username, 'group' => $group, 'duration' => $duration]
        );
    }
}
