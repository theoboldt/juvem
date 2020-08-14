<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Controller\Event\Gallery\GalleryPublicController;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Event;
use AppBundle\Entity\Geo\ClimaticInformationInterface;
use AppBundle\Entity\Geo\LocationDescription;
use AppBundle\Entity\Geo\OpenWeatherMapWeatherCondition;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Manager\Geo\AddressResolverInterface;
use AppBundle\Manager\Geo\DetailedOpenStreetMapPlace;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class LocationController extends Controller
{
    
    const LABEL_UNKNOWN = 'Unbekannt';
    
    /**
     * Get participants location distribution
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid", "include" = "participants"})
     * @Route("/admin/event/{eid}/participants-location.json", requirements={"eid": "\d+"}, name="event_participants_location_data")
     * @Security("is_granted('participants_read', event)")
     * @param Request $request
     * @param Event $event
     * @return JsonResponse
     */
    public function participantsLocationDistribution(Event $event): Response
    {
        /** @var AddressResolverInterface $addressResolver */
        $addressResolver = $this->get('app.geo.address_resolver');
        
        $postcodes = [];
        $chain     = [];
        $unknown   = 0;
        
        /** @var Participation $participation */
        foreach ($event->getParticipations() as $participation) {
            $location = $addressResolver->provideCoordinates($participation);
            
            /** @var Participant $participant */
            foreach ($participation->getParticipants() as $participant) {
                if ($participant->isConfirmed() && !$participant->isWithdrawn() && !$participant->isRejected()) {
                    if ($location->isLocationProvided()) {
                        $details   = $location->getDetails();
                        $addresses = [];
                        if (isset($details['address'])) {
                            $addresses = $details['address'];
                        } else {
                            foreach ($details as $detail) {
                                $addresses = $detail['address'];
                            }
                        }
                        
                        $country       = $addresses['country'] ?? self::LABEL_UNKNOWN;
                        $state         = $addresses['state'] ?? self::LABEL_UNKNOWN;
                        $boundary      = $addresses['county'] ?? self::LABEL_UNKNOWN;
                        $city          = $addresses['town'] ?? self::LABEL_UNKNOWN;
                        $village       = $addresses['town'] ?? self::LABEL_UNKNOWN;
                        $neighbourhood = $addresses['neighbourhood'] ?? self::LABEL_UNKNOWN;
                        
                        $postcode = $location->getAddressTagsZip(true);
                        foreach (array_reverse($addresses) as $address) {
                            if (!isset($address['type'])) {
                                continue;
                            }
                            $localname = $address['localname'];
                            switch ($address['type']) {
                                case 'postcode':
                                    $postcode = $localname;
                                    break;
                                case 'country':
                                    $country = $localname;
                                    break;
                                case 'administrative':
                                    if ($address['place_type'] === 'state') {
                                        $state = $localname;
                                    } else {
                                        $a = 1;
                                        if ($address['class'] === 'boundary') {
                                            if ($address['rank_address'] < 13) {
                                                $boundary = $localname;
                                            } elseif (in_array($address['place_type'], ['village', 'town', 'city'])) {
                                                if ($village !== self::LABEL_UNKNOWN
                                                    || (isset($village['rank_address'])
                                                        && $address['rank_address'] < $village['rank_address'])
                                                ) {
                                                    $village = $address;
                                                }
                                                $city = $localname;
                                            }
                                        }
                                    }
                                    break;
                                case 'neighbourhood':
                                    if ($address['class'] === 'place') {
                                        $neighbourhood = $localname;
                                    }
                                    break;
                                case 'hamlet':
                                    if (empty($neighbourhood)) {
                                        $neighbourhood = $localname;
                                    }
                                    break;
                                case 'village':
                                    if ($address['class'] === 'place') {
                                        if ($village !== self::LABEL_UNKNOWN
                                            || (isset($village['rank_address']) &&
                                                $address['rank_address'] < $village['rank_address'])
                                        ) {
                                            $village = $address;
                                        }
                                    }
                                    break;
                                
                            }
                        }
                        if (is_array($village)) {
                            $village = $village['localname'];
                        }
                        if ($village === self::LABEL_UNKNOWN) {
                            $village = $city;
                        }
                        
                        if (!isset($chain[$country])) {
                            $chain[$country] = self::initializeElement($country);
                        }
                        ++$chain[$country]['o'];
                        
                        if (!isset($chain[$country]['c'][$state])) {
                            $chain[$country]['c'][$state] = self::initializeElement($state);
                        }
                        ++$chain[$country]['c'][$state]['o'];
                        
                        if (!isset($chain[$country]['c'][$state]['c'][$boundary])) {
                            $chain[$country]['c'][$state]['c'][$boundary] = self::initializeElement($boundary);
                        }
                        ++$chain[$country]['c'][$state]['c'][$boundary]['o'];
                        
                        if (!isset($chain[$country]['c'][$state]['c'][$boundary]['c'][$city])) {
                            $chain[$country]['c'][$state]['c'][$boundary]['c'][$city] = self::initializeElement(
                                $city
                            );
                        }
                        ++$chain[$country]['c'][$state]['c'][$boundary]['c'][$city]['o'];
                        
                        if (!isset($chain[$country]['c'][$state]['c'][$boundary]['c'][$city]['c'][$village])) {
                            $chain[$country]['c'][$state]['c'][$boundary]['c'][$city]['c'][$village]
                                = self::initializeElement($village);
                        }
                        ++$chain[$country]['c'][$state]['c'][$boundary]['c'][$city]['c'][$village]['o'];
                        
                        if ($neighbourhood) {
                            if (!isset($chain[$country]['c'][$state]['c'][$boundary]['c'][$city]['c'][$village]['c'][$neighbourhood])) {
                                $chain[$country]['c'][$state]['c'][$boundary]['c'][$city]['c'][$village]['c'][$neighbourhood]
                                    = self::initializeElement($neighbourhood);
                            }
                            ++$chain[$country]['c'][$state]['c'][$boundary]['c'][$city]['c'][$village]['c'][$neighbourhood]['o'];
                        }
                        
                        $postcodes[] = $postcodes ?: $location->getAddressTagsZip(true);
                        
                    } else {
                        ++$unknown;
                    }
                }
            }
        }
        
        /**
         * Compare occurrences
         *
         * @param array $a
         * @param array $b
         * @return int
         */
        $compareOccurrencesCount = function (array $a, array $b): int {
            if ($a['o'] === $b['o']) {
                if ($a['n'] !== $b['n']) {
                    return ($a['n'] > $b['n']) ? 1 : -1;
                }
                return 0;
            }
            return ($a['o'] > $b['o']) ? -1 : 1;
        };
        
        $chain[self::LABEL_UNKNOWN]      = self::initializeElement(self::LABEL_UNKNOWN);
        $chain[self::LABEL_UNKNOWN]['o'] = $unknown;
        
        $max = 0;
        $total = 0;
        foreach ($chain as &$countries) {
            $total += $countries['o'];
            usort($countries['c'], $compareOccurrencesCount);
            foreach ($countries['c'] as &$states) {
                usort($states['c'], $compareOccurrencesCount);
                foreach ($states['c'] as &$boundaries) {
                    usort($boundaries['c'], $compareOccurrencesCount);
                    foreach ($boundaries['c'] as &$cities) {
                        if ($cities['o'] > $max) {
                            $max = $cities['o'];
                        }
                        usort($cities['c'], $compareOccurrencesCount);
                        foreach ($cities['c'] as &$villages) {
                            usort($villages['c'], $compareOccurrencesCount);
                            foreach ($villages['c'] as &$neighbourhoods) {
                                usort($neighbourhoods['c'], $compareOccurrencesCount);
                            }
                        }
                    }
                }
            }
        }
        unset($countries, $state, $boundary, $cities, $villages, $neighbourhoods);
        
        return new JsonResponse(['distribution' => array_values($chain), 'total' => $total, 'max' => $max]);
    }
    
    /**
     * Initialize a chain element
     *
     * @param string $name
     * @return array
     */
    private static function initializeElement(string $name): array
    {
        return [
            'n' => $name,
            'o' => 0,
            'c' => [],
        ];
    }
    
    /**
     * Get event coordinates
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/location.json", requirements={"eid": "\d+"}, name="event_location_location")
     * @param Request $request
     * @param Event $event
     * @return JsonResponse
     */
    public function locationInformationAction(Request $request, Event $event): Response
    {
        $response = new JsonResponse([]);
        $response->setMaxAge(14 * 24 * 60 * 60);
        if ($this->isRequestLastModifiedResponse($event, $request, $response)) {
            $response->setStatusCode(Response::HTTP_NOT_MODIFIED);
            return $response;
        }
        
        $addressResolver = $this->get('app.geo.address_resolver');
        $coordinates     = $addressResolver->provideCoordinates($event);
        
        if ($coordinates) {
            $data = [
                'coordinates' => [
                    'latitude'  => $coordinates->getLocationLatitude(),
                    'longitude' => $coordinates->getLocationLongitude(),
                ]
            ];
            $response->setData($data);
        }
        
        return $response;
    }
    
    /**
     * Extract weather information for json
     *
     * @param ClimaticInformationInterface $climate
     * @return array
     */
    private static function extractWeatherList(ClimaticInformationInterface $climate)
    {
        $weatherList = [];
        foreach ($climate->getWeather() as $weatherCondition) {
            $weather = [
                'description' => $weatherCondition->getDescription(),
            ];
            if ($weatherCondition instanceof OpenWeatherMapWeatherCondition) {
                $weather['id']   = $weatherCondition->getId();
                $weather['icon'] = $weatherCondition->getIcon();
            }
            $weatherList[] = $weather;
        }
        return $weatherList;
    }
    
    
    /**
     * Get event current weather
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/meteorologocial_information.json", requirements={"eid": "\d+"}, name="event_meteorological")
     * @param Request $request
     * @param Event $event
     * @return JsonResponse
     */
    public function meteorologicalInformationAction(Request $request, Event $event): Response
    {
        $response = new JsonResponse([]);
        $response->setMaxAge(30 * 60);
        if ($this->isRequestLastModifiedResponse($event, $request, $response)) {
            $response->setStatusCode(Response::HTTP_NOT_MODIFIED);
            return $response;
        }
        
        $addressResolver = $this->get('app.geo.address_resolver');
        $coordinates     = $addressResolver->provideCoordinates($event);
        
        $data = [
            'current'            => [],
            'forecast'           => [],
            'forecast_available' => false,
        ];
        if ($coordinates) {
            $weatherProvider = $this->get('app.geo.weather_provider');
            $climate         = $weatherProvider->provideCurrentWeather($coordinates);
            if ($climate) {
                $weatherList     = $this->extractWeatherList($climate);
                $data['current'] = [
                    'pressure'               => $climate->getPressure(),
                    'humidity_relative'      => $climate->getRelativeHumidity(),
                    'temperature'            => round($climate->getTemperature()),
                    'temperature_feels_like' => round($climate->getTemperatureFeelsLike()),
                    'weather'                => $weatherList,
                ];
                if ($climate instanceof ProvidesCreatedInterface) {
                    $dataCreatedAt                 = $climate->getCreatedAt()->format(Event::DATE_FORMAT_DATE);
                    $dataCreatedAt                 .= ' um ';
                    $dataCreatedAt                 .= $climate->getCreatedAt()->format(Event::DATE_FORMAT_TIME);
                    $data['current']['created_at'] = $dataCreatedAt;
                }
            }
            
            $begin = clone $event->getStartDate();
            $begin->setTime(0, 0, 0);
            if ($event->hasEndDate()) {
                $end = clone $event->getEndDate();
            } else {
                $end = clone $begin;
            }
            $end->setTime(23, 59, 59);
            
            $forecast = $weatherProvider->provideForecastWeather($coordinates, $begin, $end);
            if ($forecast) {
                $forecastResult = [];
                $climateDates   = [];
                $forecastTimes  = [];
                
                foreach ($forecast->getElements() as $climate) {
                    $climateDate = $climate->getDate();
                    $climateHour = (int)$climateDate->format('H');
                    if ($climateHour < 4 || $climateHour > 22) {
                        continue; //exclude these hours from result
                    }
                    
                    $weatherList = $this->extractWeatherList($climate);
                    $climateTime = $climateDate->format(Event::DATE_FORMAT_TIME);
                    $climateDay  = [
                        'day'   => (int)$climateDate->format('j'),
                        'month' => substr(
                            GalleryPublicController::convertMonthNumber((int)$climateDate->format('m')), 0, 3
                        ),
                        'times' => []
                    ];
                    $climateDate = $climateDate->format('Y-m-d');
                    
                    $forecastTimes[$climateTime] = $climateTime;
                    $climateDates[$climateDate]  = $climateDay;
    
                    if (!isset($forecastResult[$climateDate])) {
                        $forecastResult[$climateDate] = $climateDay;
                    }
                    $forecastResult[$climateDate]['times'][$climateTime] = [
                        'time'     => $climateTime,
                        'forecast' => [
                            'temperature'            => round($climate->getTemperature()),
                            'temperature_feels_like' => round($climate->getTemperatureFeelsLike()),
                            'weather'                => $weatherList,
                        ],
                    ];
                }
                
                foreach ($forecastResult as $date => $times) {
                    foreach ($forecastTimes as $forecastTime) {
                        if (!isset($forecastResult[$date]['times'][$forecastTime])) {
                            $forecastResult[$date]['times'][$forecastTime] = [
                                'time' => $forecastTime, 'forecast' => new \ArrayObject()
                            ];
                        }
                        ksort($forecastResult[$date]['times']);
                    }
                }
                ksort($forecastResult);
                
                $data['forecast']           = $forecastResult;
                $data['forecast_available'] = (bool)count($forecastResult);
            }
            $response->setData($data);
        }
        
        return $response;
    }
    
    
    /**
     * Get event current weather
     *
     * @param Request $request
     * @param Event $event
     * @return JsonResponse
     * @deprecated
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/weather_current.json", requirements={"eid": "\d+"}, name="event_weather_current")
     */
    public function currentWeatherInformationAction(Request $request, Event $event): Response
    {
        $response = new JsonResponse([]);
        $response->setMaxAge(30 * 60);
        if ($this->isRequestLastModifiedResponse($event, $request, $response)) {
            $response->setStatusCode(Response::HTTP_NOT_MODIFIED);
            return $response;
        }
        
        $addressResolver = $this->get('app.geo.address_resolver');
        $coordinates     = $addressResolver->provideCoordinates($event);
        
        if ($coordinates) {
            $weatherProvider = $this->get('app.geo.weather_provider');
            $climate         = $weatherProvider->provideCurrentWeather($coordinates);
            if ($climate) {
                $weatherList = $this->extractWeatherList($climate);
                $data        = [
                    'pressure'               => $climate->getPressure(),
                    'humidity_relative'      => $climate->getRelativeHumidity(),
                    'temperature'            => round($climate->getTemperature()),
                    'temperature_feels_like' => round($climate->getTemperatureFeelsLike()),
                    'weather'                => $weatherList,
                ];
                if ($climate instanceof ProvidesCreatedInterface) {
                    $datsCreatedAt      = $climate->getCreatedAt()->format(Event::DATE_FORMAT_DATE);
                    $datsCreatedAt      .= ' um ';
                    $datsCreatedAt      .= $climate->getCreatedAt()->format(Event::DATE_FORMAT_TIME);
                    $data['created_at'] = $datsCreatedAt;
                }
                
                $response->setData($data);
            }
        }
        
        return $response;
    }
    
    /**
     * Configure event last modified response header
     *
     * @param Event $event
     * @param Request $request
     * @param Response $response
     * @return bool
     */
    private function isRequestLastModifiedResponse(Event $event, Request $request, Response $response)
    {
        $lastModified = $event->getModifiedAt();
        $eTag         = $event->getEid();
        $eTag         .= ($lastModified ? $lastModified->format('U') : $event->getCreatedAt()->format('U'));
        $response->setLastModified($lastModified)
                 ->setETag(sha1($eTag))
                 ->setPublic();
        if ($response->getMaxAge()) {
            $response->setMaxAge(1 * 24 * 60 * 60);
        }
        
        return $response->isNotModified($request);
    }
    
}
