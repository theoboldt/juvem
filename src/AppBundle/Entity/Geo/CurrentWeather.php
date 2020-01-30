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
 * @ORM\Table(name="weather_current")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Geo\CurrentWeatherRepository")
 */
class CurrentWeather implements CoordinatesAwareInterface, ClimaticInformationInterface, ProvidesCreatedInterface
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
     * @return CurrentWeather
     */
    public static function createDetailedForLocation(
        string $provider, array $details, float $locationLatitude, float $locationLongitude
    ): CurrentWeather
    {
        $weather = new self($provider, $details);
        $weather->setLocation($locationLatitude, $locationLongitude);
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
     * @return CurrentWeatherDetails|OpenWeatherMapCurrentWeatherDetails
     */
    public function getDetails(): CurrentWeatherDetails
    {
        switch ($this->getProvider()) {
            case self::PROVIDER_OPENWEATHERMAP:
                return new OpenWeatherMapCurrentWeatherDetails($this->details);
            default:
                return new CurrentWeatherDetails($this->details);
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
        if ($this->locationLatitude === null && isset($this->details['coord']['lat'])) {
            $this->locationLatitude = (float)$this->details['coord']['lat'];
        }
        return $this->locationLatitude;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getLocationLongitude(): ?float
    {
        if ($this->locationLongitude === null && isset($this->details['coord']['lon'])) {
            $this->locationLongitude = (float)$this->details['coord']['lon'];
        }
        return null;
    }
    
    /**
     * @inheritDoc
     */
    public function isLocationProvided(): bool
    {
        return $this->getLocationLatitude() && $this->getLocationLongitude();
    }
    
    /**
     * @inheritDoc
     */
    public function getTemperature(): float
    {
        return (float)$this->details['main']['temp'];
    }
    
    /**
     * @inheritDoc
     */
    public function getTemperatureFeelsLike(): float
    {
        return (float)$this->details['main']['feels_like'];
    }
    
    /**
     * @inheritDoc
     */
    public function getPressure(): int
    {
        return (int)$this->details['main']['pressure'];
    }
    
    /**
     * @inheritDoc
     */
    public function getRelativeHumidity(): int
    {
        return (int)$this->details['main']['humidity'];
    }
    
    /**
     * @inheritDoc
     */
    public function getWeather(): array
    {
        $result = [];
        foreach ($this->details['weather'] as $weather) {
            switch ($this->getProvider()) {
                case self::PROVIDER_OPENWEATHERMAP:
                    $item = new OpenWeatherMapWeatherCondition($weather);
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown weather provider');
            }
            $result[] = $item;
        }
        return $result;
    }
}