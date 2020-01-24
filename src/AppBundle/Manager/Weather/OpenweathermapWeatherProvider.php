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
use AppBundle\Manager\Geo\CoordinatesAwareInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class OpenweathermapWeatherProvider implements WeatherProviderInterface
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
     * OpenweathermapWeatherProvider constructor.
     *
     * @param string|array|string[] $apiKeys
     */
    public function __construct($apiKeys)
    {
        if (!is_array($apiKeys)) {
            explode(';', $apiKeys);
        }
        $this->apiKeys = $apiKeys;
    }
    
    /**
     * Get a key
     *
     * @return string
     */
    private function provideApiKey(): string
    {
        return array_rand($this->apiKeys);
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
        return new CurrentWeather($data);
    }
}