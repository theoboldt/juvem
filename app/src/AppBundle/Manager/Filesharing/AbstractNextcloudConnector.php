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
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractNextcloudConnector
{
    
    /**
     * @var NextcloudConnectionConfiguration
     */
    protected NextcloudConnectionConfiguration $configuration;
    
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;
    
    /**
     * Cached HTTP client
     *
     * @var Client|null
     */
    protected ?Client $client = null;
    
    /**
     * NextcloudWebDavConnector constructor.
     *
     * @param NextcloudConnectionConfiguration $configuration
     * @param LoggerInterface|null $logger
     */
    public function __construct(NextcloudConnectionConfiguration $configuration, ?LoggerInterface $logger = null)
    {
        $this->configuration = $configuration;
        $this->logger        = $logger ?? new NullLogger();
    }
    
    /**
     * Configures the Guzzle client for juvimg service
     *
     * @return Client
     */
    abstract protected function client(): Client;
    
    /**
     * Extract response as {@see \SimpleXMLElement}
     *
     * @param ResponseInterface $response
     * @return \SimpleXMLElement
     */
    protected static function extractXmlResponse(ResponseInterface $response): \SimpleXMLElement
    {
        $expectedContentTypes = ['application/xml; charset=utf-8', 'text/xml; charset=utf-8'];
        if (in_array(mb_strtolower($response->getHeaderLine('Content-Type')), $expectedContentTypes, true)) {
            $content = $response->getBody()->getContents();
            if (empty($content) && $response->getStatusCode() === 405) {
                throw new NextcloudOperationFailedException(
                    'Method not allowed, empty response provided'
                );
            }
            $xml     = new \SimpleXMLElement($content);
            return $xml;
        } else {
            throw new NextcloudOperationFailedException(
                sprintf('Unexpected content type "%s" transmitted', $response->getHeaderLine('Content-Type'))
            );
        }
    }
    
    /**
     * Extract
     *
     * @param \SimpleXMLElement $xml
     * @param string $xpath
     * @return string|null
     */
    protected static function extractXmlProperty(\SimpleXMLElement $xml, string $xpath): ?string
    {
        foreach ($xml->xpath($xpath) as $fileId) {
            return (string)$fileId;
        }
        return null;
    }
}
