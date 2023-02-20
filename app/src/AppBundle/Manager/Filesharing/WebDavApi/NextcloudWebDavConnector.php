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
use AppBundle\Manager\Filesharing\NextcloudManager;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class NextcloudWebDavConnector extends AbstractNextcloudConnector
{
    const API_PATH = '/remote.php/dav/files/';
    
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
                        'User-Agent' => NextcloudManager::USER_AGENT . ' <webdav>',
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
     * Get directories url including user name
     *
     * @return string
     */
    private function provideEventDirectoryBaseUrl()
    {
        return self::API_PATH . $this->configuration->getUsername() . '/';
    }
    
    /**
     * Get path to transmitted sub path, including name of configured event folder in first level
     *
     * @param string $subPath Sub path
     * @return string Path
     */
    private function provideEventDirectoryPath(string $subPath): string
    {
        return ltrim($this->configuration->getFolder(), '/') . '/' . ltrim($subPath, '/');
    }
    
    /**
     * List a directory
     *
     * @param string $listingHref
     * @return \Generator
     */
    public function listDirectory(string $listingHref): \Generator
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
            $listingHref,
            [RequestOptions::BODY => $requestXml]
        );
        $duration = round((microtime(true) - $start) * 1000);
        $this->logger->debug(
            'Fetched nextcloud directory listing for path {path} within {duration} ms',
            ['path' => $listingHref, 'duration' => $duration]
        );
        
        if ($response->getStatusCode() === 404) {
            throw new NextcloudWebDavDirectoryNotFoundException(sprintf('Path "%s" not found', $listingHref));
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
     * Fetch file and provide response stream
     * 
     * @param NextcloudFileInterface|string $nextcloudFile
     * @return StreamInterface
     */
    public function fetchFileStream($nextcloudFile): StreamInterface
    {
        if ($nextcloudFile instanceof NextcloudFileInterface) {
            $href = $nextcloudFile->getHref();
        } elseif (is_string($nextcloudFile)) {
            $href = $nextcloudFile;
        } else {
            throw new \InvalidArgumentException('Need to pass instance of '.NextcloudFile::class.' or href string');
        }
        $response = $this->client()->get($href);
        return $response->getBody();
    }
    
    /**
     * Fetch event root directory for transmitted root name
     *
     * @param string $eventDirectoryRootName
     * @return NextcloudDirectory|null
     */
    public function fetchEventRootDirectory(string $eventDirectoryRootName): ?NextcloudDirectory
    {
        foreach ($this->listEventRootDirectories() as $directory) {
            if ($directory->getName() === $eventDirectoryRootName) {
                return $directory;
            }
        }
        return null;
    }
    
    public function move() {
        $response = $this->request(
            'PROPFIND',
            self::API_PATH . 'files/juvem/Talk'
        );
        $r = $response->getBody()->getContents();

        $response = $this->request(
            'MOVE',
            self::API_PATH . 'files/theoboldt.erik/Talk',
            [
                RequestOptions::HEADERS => [
                    'Destination' => $this->configuration->getBaseUri() . self::API_PATH .
                                     'files/theoboldt.erik/Freizeiten',
                ],
            ]
        );
        $r = $response->getBody()->getContents();
        $a=1;
    }
    
    /**
     * List all existing event directories
     *
     * @return \Traversable|NextcloudDirectory[]
     */
    public function listEventRootDirectories(): \Traversable
    {
        $listing = $this->listDirectory($this->provideEventDirectoryBaseUrl() . $this->provideEventDirectoryPath('/'));
        
        $directories = [];
        /** @var NextcloudFileInterface $item */
        foreach ($listing as $item) {
            if ($item instanceof NextcloudDirectory && $item->getName() !== '') {
                yield $item;
            }
        }
        
        return $directories;
    }
    
    /**
     * Urlencode except "/" character
     *
     * @param string $path
     * @return string
     */
    private static function urlencodeWebDavPath(string $path): string
    {
        $pathParts = explode('/', $path);
        foreach ($pathParts as &$pathPart) {
            $pathPart = rawurlencode($pathPart);
        }
        unset($pathPart);
        return trim(implode('/', $pathParts), '/');
    }
    
    /**
     * Create sub directory at transmitted one
     *
     * @param NextcloudDirectory $directory Directory
     * @param string $subDirectoryName      New Sub directory name (not encoded)
     * @return NextcloudDirectory Created directory
     */
    public function createSubDirectory(NextcloudDirectory $directory, string $subDirectoryName): NextcloudDirectory
    {
        return $this->createSubDirectoryAtHref($directory->getHref(false), $subDirectoryName);
    }
    
    /**
     * Create sub directory in transmitted WebDAV href
     *
     * @param string $href             Root WebDAV href
     * @param string $subDirectoryName New Sub directory name (not encoded)
     * @return NextcloudDirectory Created directory
     */
    private function createSubDirectoryAtHref(string $href, string $subDirectoryName): NextcloudDirectory
    {
        $start    = microtime(true);
        $url      = $href . '/' . rawurlencode($subDirectoryName);
        $response = $this->request(
            'MKCOL',
            $url
        );
        if ($response->getStatusCode() !== 201) {
            throw new NextcloudWebDavDirectoryCreateFailedException(
                sprintf('Failed to create "%s" with code %d', $url, $response->getStatusCode())
            );
        }
        
        $directory = null;
        foreach ($this->listDirectory($href) as $file) {
            if ($file instanceof NextcloudDirectory) {
                if ($file->getHref(false) !== $href && $file->getName() === $subDirectoryName) {
                    $directory = $file;
                    break;
                }
            }
        }
        
        $duration = round((microtime(true) - $start) * 1000);
        $this->logger->debug(
            'Created nextcloud event {url} within {duration} ms',
            ['url' => $url, 'duration' => $duration]
        );
        if ($directory) {
            return $directory;
        } else {
            throw new NextcloudWebDavDirectoryCreateFailedException(
                sprintf('Path "%s" should have been created, but not found', $url)
            );
        }
    }
    
    /**
     * Create directory for event in event directory
     *
     * @param string $name
     * @return NextcloudDirectory
     */
    public function createEventRootDirectory(string $name): NextcloudDirectory
    {
        return $this->createSubDirectoryAtHref(
            $this->provideEventDirectoryBaseUrl() . $this->provideEventDirectoryPath('/'), $name
        );
    }


    /**
     * Delete a directory from webDAV
     * 
     * @param string $directoryHref
     */
    public function deleteDirectory(string $directoryHref): void
    {
        $start    = microtime(true);
        $response = $this->request(
            'DELETE',
            $directoryHref
        );
        $duration = round((microtime(true) - $start) * 1000);
        $this->logger->debug(
            'Deleted nextcloud directory {href} within {duration} ms',
            ['href' => $directoryHref, 'duration' => $duration]
        );
        if ($response->getStatusCode() !== 204) {
            throw new NextcloudWebDavDirectoryDeleteFailedException(
                sprintf('Failed to delete "%s" with code %d', $directoryHref, $response->getStatusCode())
            );
        }
    }
    
}
