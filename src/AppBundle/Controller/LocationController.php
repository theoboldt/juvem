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

use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Entity\Event;
use AppBundle\Entity\Geo\CurrentWeather;
use AppBundle\Entity\Geo\OpenWeatherMapCurrentWeatherDetails;
use AppBundle\Entity\Geo\OpenWeatherMapWeatherCondition;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class LocationController extends Controller
{
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
     * Get event current weather
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/weather_current.json", requirements={"eid": "\d+"}, name="event_weather_current")
     * @param Request $request
     * @param Event $event
     * @return JsonResponse
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
                $data = [
                    'pressure'               => $climate->getPressure(),
                    'humidity_relative'      => $climate->getRelativeHumidity(),
                    'temperature'            => round($climate->getTemperature()),
                    'temperature_feels_like' => round($climate->getTemperatureFeelsLike()),
                    'weather'                => $weatherList,
                ];
                if ($climate instanceof ProvidesCreatedInterface) {
                    $datsCreatedAt = $climate->getCreatedAt()->format(Event::DATE_FORMAT_DATE);
                    $datsCreatedAt .= ' um ';
                    $datsCreatedAt .= $climate->getCreatedAt()->format(Event::DATE_FORMAT_TIME);
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