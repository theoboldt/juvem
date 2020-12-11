<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Filesharing\OcsShareApi;


use AppBundle\Manager\Filesharing\AbstractNextcloudConnector;
use AppBundle\Manager\Filesharing\NextcloudDirectory;
use AppBundle\Manager\Filesharing\OcsApi\NextcloudOcsOperationFailedException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class NextcloudOcsShareConnector extends AbstractNextcloudConnector
{
    const API_PATH = '/ocs/v2.php/apps/files_sharing/api/v1/';
    
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
     * @param NextcloudDirectory $directory
     * @param string $group
     */
    public function createShare(NextcloudDirectory $directory, string $group)
    {
        $start      = microtime(true);
        $path       = '/' . implode('/', $directory->getPath(true));
        $response   = $this->request(
            'POST',
            'shares',
            [
                'form_params' => [
                    'path'        => $path,
                    'shareType'   => '1',
                    'shareWith'   => $group,
                    'permissions' => (1 + 2 + 4 + 8)
                ]
            ]
        );
        $xml        = self::extractXmlResponse($response);
        $statusCode = (int)self::extractXmlProperty($xml, '//ocs/meta/statuscode');
        $status     = self::extractXmlProperty($xml, '//ocs/meta/status');
        $message    = self::extractXmlProperty($xml, '//ocs/meta/message');
        
        if ($statusCode !== 200) {
            throw new NextcloudOcsShareCreationFailedException(
                sprintf(
                    'Failed to create share for group "%s" of href "%s", message: %s',
                    $group,
                    $directory->getHref(),
                    $message
                )
            );
        }
        $duration = round((microtime(true) - $start) * 1000);
        $this->logger->debug(
            'Created nextcloud share of path {path} for group {group} within {duration} ms',
            ['path' => $path, 'group' => $group, 'duration' => $duration]
        );
    }
}
