<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager\Calendar\CalDav;

use AppBundle\Manager\Calendar\CalendarConnectionConfiguration;
use AppBundle\Manager\Calendar\CalendarOperationFailedException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

/**
 * @url https://sabre.io/dav/building-a-caldav-client/
 */
class CalDavConnector
{
    const USER_AGENT = 'Juvem/0.9';

    /**
     * @var CalendarConnectionConfiguration
     */
    private CalendarConnectionConfiguration $configuration;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Cached HTTP client
     *
     * @var Client|null
     */
    private ?Client $client = null;

    /**
     * Ctag of calendar if fetched
     *
     * @var string|null
     */
    private ?string $ctag;

    /**
     * @param CalendarConnectionConfiguration $configuration
     * @param LoggerInterface                 $logger
     */
    public function __construct(
        CalendarConnectionConfiguration $configuration,
        LoggerInterface                 $logger
    ) {
        $this->configuration = $configuration;
        $this->logger        = $logger;
    }

    /**
     * Get calendar change tag
     *
     * @return string|null
     */
    public function getCtag(): ?string
    {
        if ($this->ctag === null) {
            $configuration = $this->fetchCalendarConfiguration();
            if ($configuration) {
                $this->ctag = $configuration['ctag'];
            }
        }
        return $this->ctag;
    }

    /**
     * @return string|null
     */
    public function getPublicUri(): ?string
    {
        return $this->configuration->getPublicUri();
    }

    /**
     * @return bool
     */
    public function hasPublicUri(): bool
    {
        return $this->configuration->hasPublicUri();
    } 
    
    /**
     * Delete calendar object
     *
     * @param string $name
     * @return void
     */
    public function removeCalendarObject(string $name)
    {
        $start      = microtime(true);
        $response   = $this->request(
            'DELETE',
            urlencode($name) . '.ics',
            [
                RequestOptions::HEADERS => [
                    'Content-Type' => 'text/calendar; charset=utf-8',
                ],
            ]
        );
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200 && $statusCode !== 204) {
            $content = $response->getBody()->getContents();
            throw new CalendarOperationFailedException(
                'Response code ' . $response->getStatusCode() .
                ' provided while trying to delete calendar object "' . $name . '"'
            );
        }
        $duration = round((microtime(true) - $start) * 1000);
        $this->logger->info(
            'Deleted calendar object for item {name} within {duration} ms',
            ['name' => $name, 'duration' => $duration]
        );
    }

    /**
     * Create calendar object
     *
     * @param string    $name      Unique identifier of item
     * @param VCalendar $vcalendar Calendar item
     * @return null|string
     */
    public function updateCalendarObject(
        string    $name,
        VCalendar $vcalendar
    ): ?string {
        $start       = microtime(true);
        $requestBody = $vcalendar->serialize();
        $response    = $this->request(
            'PUT',
            urlencode($name) . '.ics',
            [
                RequestOptions::BODY    => $requestBody,
                RequestOptions::HEADERS => [
                    'Content-Type' => 'text/calendar; charset=utf-8',
                ],
            ]
        );

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 201 && $statusCode !== 204) {
            $content = $response->getBody()->getContents();
            throw new CalendarOperationFailedException(
                'Response code ' . $response->getStatusCode() . ' provided while trying to create calendar object "' .
                $name . '"'
            );
        }

        $duration = round((microtime(true) - $start) * 1000);
        $this->logger->info(
            'Updated calendar object for item {name} within {duration} ms',
            ['name' => $name, 'duration' => $duration]
        );

        $etags = $response->getHeader('ETag');
        if (count($etags)) {
            $etag = $etags[0];
            return $etag;

        } else {
            return null;
        }
    }

    /**
     * @return CalDavEvent[]
     */
    public function fetchCalendarObjects(): array
    {
        $requestXml = '<?xml version="1.0"?>
<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
    <d:prop>
        <d:getetag />
        <c:calendar-data />
    </d:prop>
    <c:filter>
        <c:comp-filter name="VCALENDAR" />
    </c:filter>
</c:calendar-query>';

        $start    = microtime(true);
        $response = $this->request(
            'REPORT',
            '',
            [
                RequestOptions::BODY    => $requestXml,
                RequestOptions::HEADERS => [
                    'Depth' => '1',
                ],
            ]
        );

        $xml = $this->extractXmlResponse($response);
        $r   = $xml->xpath('//d:multistatus/d:response');

        $calendarObjects = [];

        foreach ($r as $xmlResponse) {
            $href         = self::extractXmlProperty($xmlResponse, 'd:href');
            $etag         = self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/d:getetag');
            $calendarData = self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/cal:calendar-data');
            $status       = self::extractXmlProperty($xmlResponse, 'd:propstat/d:status');

            $calendarDocument = Reader::read($calendarData, Reader::OPTION_FORGIVING);
            if ($calendarDocument instanceof VCalendar) {
                $calendarObjects[] = new CalDavEvent(
                    $href, $etag, $calendarDocument, $status
                );

            } else {
                $this->logger->warning(
                    'CalDav object with href {href} and status {status} parsed as {class}',
                    [
                        'href'   => $href,
                        'status' => $status,
                        'class'  => get_class($calendarDocument),
                    ]
                );
            }
        }

        $duration = round((microtime(true) - $start) * 1000);
        $this->logger->info(
            'Fetched {count} calendar object for within {duration} ms',
            ['count' => count($calendarObjects), 'duration' => $duration]
        );

        return $calendarObjects;
    }


    /**
     * Fetch calendar configuration
     *
     * @return array|null
     */
    public function fetchCalendarConfiguration(): ?array
    {
        $requestXml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:cs="http://calendarserver.org/ns/">
  <d:prop>
     <d:displayname />
     <cs:getctag />
  </d:prop>
</d:propfind>';

        $start    = microtime(true);
        $response = $this->request(
            'PROPFIND',
            '',
            [RequestOptions::BODY => $requestXml]
        );
        $duration = round((microtime(true) - $start) * 1000);
        $this->logger->info(
            'Fetched calendar configuration within {duration} ms',
            ['duration' => $duration]
        );

        $xml = $this->extractXmlResponse($response);
        $r   = $xml->xpath('//d:multistatus/d:response');
        foreach ($r as $xmlResponse) {
            $href = self::extractXmlProperty($xmlResponse, 'd:href');
            if (strpos($this->configuration->getBaseUri(), trim($href, '/')) !== false) {
                $configuration = [
                    'displayname' => self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/d:displayname'),
                    'ctag'        => self::extractXmlProperty($xmlResponse, 'd:propstat/d:prop/cs:getctag'),
                    'status'      => self::extractXmlProperty($xmlResponse, 'd:propstat/d:status'),
                ];
                if (strpos($configuration['status'], '200') === false) {
                    $this->logger->error(
                        'Status of calendar {displayname} is {status}, ctag is {ctag}', $configuration
                    );
                }
                return $configuration;
            }
        }
        return null;
    }

    /**
     * Configures the Guzzle client
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
                        'User-Agent' => self::USER_AGENT . ' <caldav>',
                    ],
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
     * @return ResponseInterface
     */
    private function request(string $method, string $subUri, array $options = []): ResponseInterface
    {
        try {
            $response = $this->client()->request(
                $method, $subUri, $options
            );
        } catch (\Exception $e) {
            throw new CalDavOperationFailedException(
                'Request failed: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return $response;
    }


    /**
     * Create instance
     *
     * @param string|null          $baseUri
     * @param string|null          $username
     * @param string|null          $password
     * @param string|null          $publicUri
     * @param LoggerInterface|null $logger
     * @return CalDavConnector|null
     */
    public static function create(
        ?string         $baseUri = '',
        ?string         $username = '',
        ?string         $password = '',
        ?string         $publicUri = '',
        LoggerInterface $logger = null
    ): ?CalDavConnector {
        $baseUri  = trim($baseUri);
        $username = trim($username);
        $logger   = $logger ?? new NullLogger();

        if (empty($baseUri) || empty($username)) {
            return null;
        }

        $configuration = new CalendarConnectionConfiguration($baseUri, $username, $password, $publicUri);
        return new self($configuration, $logger);
    }

    /**
     * Extract response as {@see \SimpleXMLElement}
     *
     * @param ResponseInterface $response
     * @return \SimpleXMLElement
     */
    protected function extractXmlResponse(ResponseInterface $response): \SimpleXMLElement
    {
        $expectedContentTypes = ['application/xml; charset=utf-8', 'text/xml; charset=utf-8'];
        if ($response->getStatusCode() === 405) {
            throw new CalendarOperationFailedException(
                'Method not allowed'
            );
        } elseif (in_array(mb_strtolower($response->getHeaderLine('Content-Type')), $expectedContentTypes, true)) {
            $content    = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();
            $this->logger->debug(
                'Fetched XML response status code {status}: {content}',
                ['status' => $statusCode, 'content' => $content]
            );
            if (empty($content) && $statusCode === 405) {
                throw new CalendarOperationFailedException(
                    'Method not allowed, empty response provided'
                );
            }
            $xml = new \SimpleXMLElement($content);
            return $xml;
        } else {
            throw new CalendarOperationFailedException(
                sprintf('Unexpected content type "%s" transmitted', $response->getHeaderLine('Content-Type'))
            );
        }
    }

    /**
     * Extract
     *
     * @param \SimpleXMLElement $xml
     * @param string            $xpath
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
