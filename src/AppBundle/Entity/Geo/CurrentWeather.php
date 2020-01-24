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
class CurrentWeather implements CoordinatesAwareInterface, ClimaticInformationInterface
{
    
    use CreatedTrait;
    
    /**
     * @ORM\Column(type="integer", name="weather_id")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id
     * @var int
     */
    private $id;
    
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
     * @param array $details
     * @param float $locationLatitude
     * @param float $locationLongitude
     * @return CurrentWeather
     */
    public function createDetailedForLocation(
        array $details, float $locationLatitude, float $locationLongitude
    ): CurrentWeather
    {
        $weather = new self($details);
        $weather->setLocation($locationLatitude, $locationLongitude);
        return $weather;
    }
    
    /**
     * CurrentWeather constructor.
     *
     * @param array $details
     */
    public function __construct(array $details)
    {
        $this->details = $details;
        $this->setCreatedAtNow();
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
    public function getTemperature(): float
    {
        return (float)$this->details['main']['temp'];
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
        foreach ($this->details['main']['weather'] as $weather) {
            $result[] = new OpenWeatherWeatherCondition($weather);
        }
        return $result;
    }
}