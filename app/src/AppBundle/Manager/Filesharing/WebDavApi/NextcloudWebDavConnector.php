<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Filesharing\WebDavApi;


use AppBundle\Manager\Filesharing\AbstractNextcloudConnector;
use AppBundle\Manager\Filesharing\NextcloudDirectory;
use AppBundle\Manager\Filesharing\NextcloudFile;
use AppBundle\Manager\Filesharing\NextcloudFileInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class NextcloudWebDavConnector extends AbstractNextcloudConnector
{
    const API_PATH = '/remote.php/dav/';
    
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
                $method, $subUri, $options
            );
        } catch (\Exception $e) {
            throw new NextcloudWebDavOperationFailedException(
                'Request failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
        
        return $response;
    }
    
    /**
     * Get directories url path with escaped directories
     *
     * @param string $path
     * @return string
     */
    private function getEventDirectoriesUrlPath(string $path)
    {
        return self::API_PATH . 'files/' . $this->configuration->getUsername() . '/' .
               $this->configuration->getFolder() . '/' .
               ltrim($path, '/');
    }
    
    /**
     * List a directory
     *
     * @param string $path
     * @return \Generator
     */
    private function listDirectoryItems(string $path): \Generator
    {
        $requestXml = '<?xml version="1.0"?>
<d:propfind  xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
  <d:prop>
        <d:getlastmodified />
        <d:getetag />
        <d:getcontenttype />
        <d:resourcetype />
        <oc:fileid />
        <oc:permissions />
        <oc:size />
        <d:getcontentlength />
        <oc:share-types />
  </d:prop>
</d:propfind>';
        
        
        $start    = microtime(true);
        $response = $this->request(
            'PROPFIND',
            $path,
            [RequestOptions::BODY => $requestXml]
        );
        $duration = round(microtime(true) - $start);
        $this->logger->debug(
            'Fetched nextcloud directory listing for path {path} within {duration} s',
            ['path' => $path, 'duration' => $duration]
        );
        
        if ($response->getStatusCode() === 404) {
            throw new NextcloudWebDavDirectoryNotFoundException(sprintf('Path "%s" not found', $path));
        }
        $xml = self::extractXmlResponse($response);
        
        $r = $xml->xpath('//d:multistatus/d:response');
        foreach ($r as $xmlResponse) {
            $href          = self::extractXmlProperty($xmlResponse, 'd:href');
            $lastModified  = self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/d:getlastmodified');
            $fileId        = self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/oc:fileid');
            $size          = self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/oc:size');
            $contentType   = self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/d:getcontenttype');
            $contentLength = self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/d:getcontentlength');
            $eTag          = self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/d:getetag');
            
            if (empty($href)) {
                $this->logger->warning('Failed to extract href for nextcloud folder');
            }
            if (empty($lastModified)) {
                $this->logger->warning('Failed to extract getlastmodified for nextcloud folder');
            } else {
                $lastModified = new \DateTimeImmutable($lastModified);
            }
            if (empty($fileId)) {
                $this->logger->warning('Failed to extract fileid for nextcloud folder');
            } elseif (!is_numeric($fileId)) {
                $this->logger->error('Non-numeric filedid {fileid} occurred', ['fileid' => $fileId]);
                $fileId = (int)$fileId;
            } else {
                $fileId = (int)$fileId;
            }
            if ($size === null) {
                $this->logger->warning('Failed to extract size for nextcloud folder');
            } else {
                $size = (int)$size;
            }
            if (!$contentType || empty(trim($contentType))) {
                $contentType = null;
            }
            
            if (!empty($contentType) || !empty($contentLength)) {
                yield new NextcloudFile($href, $lastModified, $fileId, $size, $eTag, $contentType);
            } else {
                yield new NextcloudDirectory($href, $lastModified, $fileId, $size, $eTag);
            }
        }
    }
    
    /**
     * List all existing event directories
     *
     * @return array
     */
    public function listEventDirectories(): array
    {
        $listing = $this->listDirectoryItems($this->getEventDirectoriesUrlPath('/'));
        
        $directories = [];
        /** @var NextcloudFileInterface $item */
        foreach ($listing as $item) {
            if (!$item instanceof NextcloudDirectory || $item->getName() === '') {
                continue;
            }
            $directories[] = $item;
        }
        
        return $directories;
    }
    
    /**
     * Fetch a directory
     *
     * @param string $name
     * @return NextcloudDirectory|null
     * @todo  When urlescaping for webdav is possible, replace
     */
    public function fetchEventDirectory(string $name): ?NextcloudDirectory
    {
        $listing = $this->listDirectoryItems($this->getEventDirectoriesUrlPath('/'));
        /** @var NextcloudFileInterface $item */
        foreach ($listing as $item) {
            if ($item instanceof NextcloudDirectory && $item->getName() === $name) {
                return $item;
            }
        }
        return null;
    }
    
    /**
     * Create directory for event in event directory
     *
     * @param string $name
     * @return NextcloudDirectory
     */
    public function createEventDirectory(string $name): NextcloudDirectory
    {
        $start    = microtime(true);
        $response = $this->request(
            'MKCOL',
            self::API_PATH . 'files/' . $this->configuration->getUsername() . '/' . $this->configuration->getFolder() .
            '/' .
            rawurlencode($name)
        );
        if ($response->getStatusCode() !== 201) {
            throw new NextcloudWebDavDirectoryCreateFailedException(
                sprintf('Failed to create directory "%s" with code %d', $name, $response->getStatusCode())
            );
        }
        
        $directory = $this->fetchEventDirectory($name);
        
        $duration = round(microtime(true) - $start);
        $this->logger->debug(
            'Created nextcloud event directory {name} within {duration} s',
            ['name' => $name, 'duration' => $duration]
        );
        if ($directory) {
            return $directory;
        } else {
            throw new NextcloudWebDavDirectoryCreateFailedException(
                sprintf('Directory "%s" should have been created, but not found', $name)
            );
        }
    }
    
    
}
