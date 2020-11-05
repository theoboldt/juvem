<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity\Geo;


use AppBundle\Entity\Audit\CreatedTrait;
use AppBundle\Entity\Audit\ProvidesCreatedInterface;
use AppBundle\Manager\Geo\CoordinatesAwareInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="weather_forecast")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Geo\MeteorologyForecastRepository")
 */
class WeatherForecast implements CoordinatesAwareInterface, ProvidesCreatedInterface, MeteorologicalForecastInterface, \IteratorAggregate
{
    const PROVIDER_OPENWEATHERMAP = 'openweathermap';
    
    use CreatedTrait;
    
    /**
     * @ORM\Column(type="integer", name="weather_id")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id
     * @var int
     */
    private $id;
    
    /**
     * Weather data provider
     *
     * @ORM\Column(type="string", name="provider", columnDefinition="ENUM('openweathermap')")
     *
     * @var string
     */
    private $provider;
    
    /**
     * This forecast begins at
     *
     * @ORM\Column(type="datetime", name="valid_since")
     *
     * @var \DateTimeInterface
     */
    protected $validSince;
    
    /**
     * Last date of forecast
     *
     * @ORM\Column(type="datetime", name="valid_until")
     *
     * @var \DateTimeInterface
     */
    protected $validUntil;
    
    /**
     * Location latitude if already fetched
     *
     * @ORM\Column(name="latitude", type="decimal", precision=10, scale=2, nullable=true)
     *
     * @var null|float
     */
    private $locationLatitude = null;
    
    /**
     * Location longitude if already fetched
     *
     * @ORM\Column(name="longitude", type="decimal", precision=11, scale=2, nullable=true)
     *
     * @var null|float
     */
    private $locationLongitude = null;
    
    /**
     * Full API result when fetching weather
     *
     * @ORM\Column(type="json_array", length=16777215, name="details", nullable=true)
     *
     * @var array
     */
    private $details = [];
    
    /**
     * Create detailed with location info
     *
     * @param string $provider Data provider
     * @param array $details   Detailed data
     * @param float $locationLatitude
     * @param float $locationLongitude
     * @return WeatherForecast
     */
    public static function createDetailedForLocation(
        string $provider, array $details, float $locationLatitude, float $locationLongitude
    ): WeatherForecast
    {
        $weather = new self($provider, $details);
        $weather->setLocation($locationLatitude, $locationLongitude);
        $weather->getValidSince(); //calculate validity
        $weather->getValidUntil(); //calculate validity
        return $weather;
    }
    
    /**
     * CurrentWeather constructor.
     *
     * @param string $provider
     * @param array $details
     */
    public function __construct(string $provider, array $details)
    {
        $this->provider = $provider;
        $this->details  = $details;
        $this->setCreatedAtNow();
    }
    
    /**
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }
    
    /**
     * @return WeatherDetails|OpenWeatherMapWeatherDetails
     */
    public function getDetails(): WeatherDetails
    {
        switch ($this->getProvider()) {
            case self::PROVIDER_OPENWEATHERMAP:
                return new OpenWeatherMapWeatherDetails($this->details);
            default:
                return new WeatherDetails($this->details);
        }
    }
    
    /**
     * Store location information
     *
     * @param float $locationLatitude
     * @param float $locationLongitude
     */
    public function setLocation(float $locationLatitude, float $locationLongitude): void
    {
        //round to 2 decimal places to be compatible to https://openweathermap.org/api
        $this->locationLatitude  = round($locationLatitude, 2);
        $this->locationLongitude = round($locationLongitude, 2);
    }
    
    /**
     * {@inheritDoc}
     */
    public function getLocationLatitude(): ?float
    {
        if ($this->locationLatitude === null && isset($this->details['city']['coord']['lat'])) {
            $this->locationLatitude = (float)$this->details['city']['coord']['lat'];
        }
        return $this->locationLatitude;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getLocationLongitude(): ?float
    {
        if ($this->locationLongitude === null && isset($this->details['city']['coord']['lon'])) {
            $this->locationLongitude = (float)$this->details['city']['coord']['lon'];
        }
        return null;
    }
    
    /**
     * Get forecast elements
     *
     * @return \Traversable
     */
    public function getElements(): \Traversable
    {
        switch ($this->getProvider()) {
            case self::PROVIDER_OPENWEATHERMAP:
                foreach ($this->details['list'] as $forecastElementData) {
                    yield new WeatherForecastElementOpenWeatherMap($forecastElementData);
                }
                break;
            default:
                throw new \InvalidArgumentException('Unknown forecast provider');
        }
    }
    
    /**
     * @inheritDoc
     */
    public function isLocationProvided(): bool
    {
        return $this->getLocationLatitude() && $this->getLocationLongitude();
    }
    
    /**
     * Get begin of validity of forecast information
     *
     * @return \DateTimeInterface
     */
    public function getValidSince(): \DateTimeInterface
    {
        if (!$this->validSince) {
            $elements = iterator_to_array($this->getElements());
            /** @var ClimaticInformationInterface $first */
            $first            = reset($elements);
            $this->validSince = $first->getDate(new \DateTimeZone(date_default_timezone_get()));
        }
        
        return $this->validSince;
    }
    
    /**
     * Get end of validity of forecast information
     *
     * @return \DateTimeInterface
     */
    public function getValidUntil(): \DateTimeInterface
    {
        if (!$this->validUntil) {
            $elements = iterator_to_array($this->getElements());
            /** @var ClimaticInformationInterface $first */
            $last             = end($elements);
            $this->validUntil = $last->getDate(new \DateTimeZone(date_default_timezone_get()));
        }
        return $this->validUntil;
    }
    
    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return $this->getElements();
    }
}