<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Geo;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class NominatimAddressResolver implements AddressResolverInterface
{
    const BASE_URI = 'https://nominatim.openstreetmap.org';
    
    /**
     * Do not access the service frequently within this time
     */
    const COOLDOWN_TIME_MS = 1000000;
    
    /**
     * Path to file on filesystem which is touched every time an address is accessed
     *
     * @var string
     */
    private $lastAccessFile;
    
    /**
     * Cached HTTP client
     *
     * @var Client
     */
    private $client;
    
    /**
     * NominatimAddressResolver constructor.
     *
     * @param string $lastAccessFile
     */
    public function __construct(string $lastAccessFile)
    {
        $this->lastAccessFile = $lastAccessFile;
    }
    
    /**
     * Get last access time of service in microseconds
     *
     * @return float
     */
    public function lastAccessed(): float
    {
        if (file_exists($this->lastAccessFile)) {
            clearstatcache(null, $this->lastAccessFile);
            return (float)filemtime($this->lastAccessFile);
        } else {
            return (float)946720800;
        }
    }
    
    /**
     * Determine if access is allowed yet
     *
     * @return bool
     */
    public function isAccessAllowed(): bool
    {
        $allowAt = microtime(true) - (self::COOLDOWN_TIME_MS / 1000000);
        
        return ($allowAt > $this->lastAccessed());
    }
    
    /**
     * Wait until access is allowed
     *
     * @param \DateTimeInterface|null $abortAt
     * @throws NominatimWaitLimitExceededException If wait limit exceeded
     */
    public function waitForAccessAndLock(?\DateTimeInterface $abortAt = null)
    {
        for ($i = 0; $i < 1000; ++$i) {
            if ($this->isAccessAllowed()) {
                touch($this->lastAccessFile);
                clearstatcache();
                return;
            }
            if ($abortAt && ($abortAt < new \DateTimeImmutable('now', new \DateTimeZone('UTC')))) {
                
                throw new NominatimWaitLimitExceededException('Exceeded abort time limit');
            }
            usleep(100000);
        }
        throw new NominatimWaitLimitExceededException('Exceeded cycle limit of ' . $i);
    }
    
    
    /**
     * Configures the Guzzle client for juvimg service
     *
     * @return Client
     */
    private function client()
    {
        if (!$this->client) {
            $this->client = new Client(
                [
                    'base_uri'                      => self::BASE_URI,
                    RequestOptions::ALLOW_REDIRECTS => [
                        'max'             => 5,
                        'strict'          => false,
                        'referer'         => false,
                        'protocols'       => ['https'],
                        'track_redirects' => false
                    ],
                    RequestOptions::CONNECT_TIMEOUT => 5,
                    RequestOptions::TIMEOUT         => 20,
                    RequestOptions::HEADERS         => [
                        'User-Agent'      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
                        'Accept'          => 'application/json',
                        'Accept-Language' => 'de_DE, de;q=0.7'
                    ]
                ]
            );
        }
        return $this->client;
    }
    
    /**
     * Lookup place via API
     *
     * @param AddressAwareInterface $item
     * @return OpenStreetMapPlace
     */
    public function fetchPlaceBySearch(AddressAwareInterface $item)
    {
        $abortAt = new \DateTime('+30 seconds', new \DateTimeZone('UTC'));
        $this->waitForAccessAndLock($abortAt);
        $response = $this->client()->get(
            '/search',
            [
                RequestOptions::QUERY => [
                    'street'         => $item->getAddressStreetNumber() . ' ' . $item->getAddressStreetName(),
                    'city'           => $item->getAddressCity(),
                    'postalcode'     => $item->getAddressZip(),
                    'format'         => 'jsonv2',
                    'addressdetails' => 1,
                    'extratags'      => 1,
                    'namedetails'    => 1,
                    'polygon_svg'    => 1,
                    'countrycodes'   => 'de,fr,lu,be,nl,dk,ch,li,at,cz'
                ]
            ]
        );
        $result   = $response->getBody()->getContents();
        $data     = json_decode($result, true);
        if ($data === null) {
            throw new \InvalidArgumentException('Failed to fetch address info: ' . json_last_error_msg());
        }
        return new OpenStreetMapPlace($data);
    }
    
    /**
     * Enrich OSM element having osm id and osm type by details
     *
     * @param OpenStreetMapElement $place
     * @return DetailedOpenStreetMapPlace
     */
    public function fetchEnrichedPlaceByDetails(OpenStreetMapElement $place): DetailedOpenStreetMapPlace
    {
        $abortAt = new \DateTime('+20 seconds', new \DateTimeZone('UTC'));
        $this->waitForAccessAndLock($abortAt);
        $response = $this->client()->get(
            '/details',
            [
                RequestOptions::QUERY => [
                    'osmtype'         => $place->getOsmType(),
                    'osmid'           => $place->getOsmId(),
                    'format'          => 'json',
                    'addressdetails'  => 1,
                    'linkedplaces'    => 1,
                    'polygon_geojson' => 1
                ]
            ]
        );
        $result   = $response->getBody()->getContents();
        $data     = json_decode($result, true);
        if ($data === null) {
            throw new \InvalidArgumentException('Failed to fetch place details: ' . json_last_error_msg());
        }
        return DetailedOpenStreetMapPlace::upgradePlace($place, $data);
    }
    
    /**
     * Fetch next upper administrative element for blurry lookups
     *
     * @param OpenStreetMapPlace $input
     * @return DetailedOpenStreetMapPlace|null
     */
    public function fetchNextAdministrativeAddress(OpenStreetMapPlace $input)
    {
        if (!$input instanceof DetailedOpenStreetMapPlace) {
            $input = $this->fetchEnrichedPlaceByDetails($input);
        }
        $upper = $input->getNextAdministrativeAddress();
        if ($upper) {
            return $this->fetchEnrichedPlaceByDetails($upper);
        } else {
            return null;
        }
    }
    
    /**
     * Provide object providing coordination information for element if found
     *
     * @param AddressAwareInterface $item Address aware interface
     * @return CoordinatesAwareInterface|null Coordinate providing object or null if not found
     */
    public function provideCoordinates(AddressAwareInterface $item): ?CoordinatesAwareInterface
    {
        $place = $this->fetchPlaceBySearch($item);

        if ($place->isFound()) {
            return $this->fetchEnrichedPlaceByDetails($place);
        } else {
            return $place;
        }
    }
    
}