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


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

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
     * @param array  $options
     * @return \SimpleXMLElement
     */
    private function request(string $method, string $subUri, array $options = []): \SimpleXMLElement
    {
        try {
            $response = $this->client()->request(
                $method, self::API_PATH . $subUri, $options
            );
        } catch (\Exception $e) {
            throw new NextcloudWebDavOperationFailedException(
                'Request failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
        if ($response->getHeaderLine('Content-Type') === 'application/xml; charset=utf-8') {
            $content = $response->getBody()->getContents();
            $xml     = new \SimpleXMLElement($content);
            /*
            $xml->registerXPathNamespace('d', 'DAV');
            $xml->registerXPathNamespace('s', 'http://sabredav.org/ns');
            $xml->registerXPathNamespace('oc', 'http://owncloud.org/ns');
            $xml->registerXPathNamespace('nc', 'http://nextcloud.org/ns');
            */
            return $xml;
        } else {
            throw new NextcloudWebDavOperationFailedException(
                sprintf('Unexpected content type "%s" transmitted', $response->getHeaderLine('Content-Type'))
            );
        }

    }

    /**
     * List all existing event directories
     * 
     * @return array
     */
    public function listEventDirectories(): array
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

        $xml = $this->request(
            'PROPFIND',
            'files/' . $this->configuration->getUsername() . '/' . $this->configuration->getFolder(),
            [RequestOptions::BODY => $requestXml]
        );

        $hrefMainFolder = self::API_PATH . 'files/' . $this->configuration->getUsername() . '/' .
                          $this->configuration->getFolder() . '/';

        $directories = [];

        $r = $xml->xpath('//d:multistatus/d:response');
        foreach ($r as $xmlResponse) {
            $href = self::extractXmlProperty($xmlResponse, 'd:href');
            if ($href === $hrefMainFolder) {
                continue;
            }
            $lastModified = self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/d:getlastmodified');
            $fileId       = self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/oc:fileid');
            $size         = self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/oc:size');

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
            } else {
                $fileId = (int)$fileId;
            }
            if ($size === null) {
                $this->logger->warning('Failed to extract size for nextcloud folder');
            } else {
                $size = (int)$size;
            }
            if ($href && $lastModified && $fileId) {
                $name = str_replace($hrefMainFolder, '', urldecode($href));
                $name = rtrim($name, '/');
                
                $directories[] = new NextcloudDirectory($name, $lastModified, $fileId, $size);
            }
        }

        return $directories;
    }


}
