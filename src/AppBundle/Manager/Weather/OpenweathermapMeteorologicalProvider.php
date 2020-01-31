<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Weather;


use AppBundle\Entity\Geo\ClimaticInformationInterface;
use AppBundle\Entity\Geo\CurrentWeather;
use AppBundle\Entity\Geo\MeteorologicalForecastInterface;
use AppBundle\Entity\Geo\WeatherForecast;
use AppBundle\Manager\Geo\CoordinatesAwareInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class OpenweathermapMeteorologicalProvider implements WeatherCurrentProviderInterface, WeatherForecastProviderInterface
{
    const BASE_URI = 'https://api.openweathermap.org';
    
    /**
     * List of API keys to use by this provider
     *
     * @var array|string[]
     */
    private $apiKeys = [];
    
    /**
     * Cached HTTP client
     *
     * @var Client
     */
    private $client;
    
    /**
     * OpenweathermapMeteorologicalProvider constructor.
     *
     * @param string|array|string[] $apiKeys
     */
    public function __construct($apiKeys)
    {
        if (!empty($apiKeys)) {
            if (!is_array($apiKeys)) {
                explode(';', $apiKeys);
            }
            $this->apiKeys = [$apiKeys];
        }
    }
    
    /**
     * Get a key
     *
     * @return string
     */
    private function provideApiKey(): string
    {
        return $this->apiKeys[array_rand($this->apiKeys)];
    }
    
    /**
     * Determine if api keys are configured
     *
     * @return bool
     */
    public function providesApiKeys(): bool
    {
        return count($this->apiKeys);
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
                        'max'             => 2,
                        'strict'          => false,
                        'referer'         => false,
                        'protocols'       => ['https'],
                        'track_redirects' => false
                    ],
                    RequestOptions::CONNECT_TIMEOUT => 5,
                    RequestOptions::TIMEOUT         => 20,
                    RequestOptions::HEADERS         => [
                        'Accept'          => 'application/json',
                        'Accept-Language' => 'de_DE, de;q=0.7'
                    ]
                ]
            );
        }
        return $this->client;
    }
    
    /**
     * @inheritDoc
     */
    public function provideCurrentWeather(CoordinatesAwareInterface $item): ?ClimaticInformationInterface
    {
        if (!$this->providesApiKeys()) {
            return null;
        }
        $response = $this->client()->get(
            '/data/2.5/weather',
            [
                RequestOptions::QUERY => [
                    'appid' => $this->provideApiKey(),
                    'lat'   => $item->getLocationLatitude(),
                    'lon'   => $item->getLocationLongitude(),
                    'units' => 'metric',
                    'lang'  => 'de',
                ]
            ]
        );
        $result   = $response->getBody()->getContents();
        $data     = json_decode($result, true);
        if ($data === null) {
            throw new \InvalidArgumentException('Failed to fetch address info: ' . json_last_error_msg());
        }
        return CurrentWeather::createDetailedForLocation(
            CurrentWeather::PROVIDER_OPENWEATHERMAP, $data, $item->getLocationLatitude(), $item->getLocationLongitude()
        );
    }
    
    /**
     * @inheritDoc
     */
    public function provideForecastWeather(
        CoordinatesAwareInterface $item, \DateTimeInterface $begin, \DateTimeInterface $end
    ): ?MeteorologicalForecastInterface
    {
        if (!$this->providesApiKeys()) {
            return null;
        }
        
        $validityLimitBegin = new \DateTime('now');
        $validityLimitBegin->setTime(23, 59, 59);
        
        if ($end < $validityLimitBegin) {
            //requested period is completely in past, so no forecast is possible
            return null;
        }
        
        $validityLimitEnd = new \DateTime('now');
        $validityLimitEnd->setTime(0, 0, 0);
        $validityLimitEnd->modify('+5 days');
        
        if ($begin > $validityLimitEnd) {
            //requested period ends before end of possible forecast
            return null;
        }
        
        $response = $this->client()->get(
            '/data/2.5/forecast',
            [
                RequestOptions::QUERY => [
                    'appid' => $this->provideApiKey(),
                    'lat'   => $item->getLocationLatitude(),
                    'lon'   => $item->getLocationLongitude(),
                    'units' => 'metric',
                    'lang'  => 'de',
                ]
            ]
        );
        $result   = $response->getBody()->getContents();
        $data     = json_decode($result, true);
        if ($data === null) {
            throw new \InvalidArgumentException('Failed to fetch address info: ' . json_last_error_msg());
        }
        
        return WeatherForecast::createDetailedForLocation(
            WeatherForecast::PROVIDER_OPENWEATHERMAP, $data, $item->getLocationLatitude(), $item->getLocationLongitude()
        );
    }
}