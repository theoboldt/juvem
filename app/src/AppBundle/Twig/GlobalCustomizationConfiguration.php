<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Twig;


class GlobalCustomizationConfiguration implements GlobalCustomizationConfigurationProvider
{

    /**
     * Name of app
     *
     * @var string
     */
    protected $appTitle;

    /**
     * Name of organization
     *
     * @var string
     */
    protected $organizationName;

    /**
     * Address of organization, street name
     *
     * @var string
     */
    protected $addressStreet;

    /**
     * Address of organization, postal code
     *
     * @var string
     */
    protected $addressPostalCode;

    /**
     * Address of organization, postal code
     *
     * @var string
     */
    protected $addressLocality;

    /**
     * Phone number of organization
     *
     * @var string
     */
    protected $numberPhone;

    /**
     * Fax number of organization
     *
     * @var string|null
     */
    protected $numberFax;

    /**
     * E-mail address of organization
     *
     * @var string
     */
    protected $email;

    /**
     * Website of organization
     *
     * @var string|null
     */
    protected $website;

    /**
     * Facebook link if available
     *
     * @var string|null
     */
    protected $facebook;

    /**
     * Juvem base url
     *
     * @var string|null
     */
    protected $juvemWebsite;

    /**
     * If configured, link of application logo will not redirect to homepage but to specified url
     *
     * @var string|null
     */
    protected $logoLink;

    /**
     * Name of person responsible for data privacy
     *
     * @var string|null
     */
    protected $privacyResponsible;

    /**
     * GlobalCustomizationConfiguration constructor.
     *
     * @param string|null $appTitle
     * @param string      $organizationName
     * @param string      $addressStreet
     * @param string      $addressPostalCode
     * @param string      $addressLocality
     * @param string      $numberPhone
     * @param string|null $numberFax
     * @param string      $email
     * @param string|null $website
     * @param string|null $facebook
     * @param string|null $juvemWebsite
     * @param string|null $logoLink
     * @param string|null $privacyResponsible
     */
    public function __construct(
        ?string $appTitle,
        string $organizationName,
        string $addressStreet,
        string $addressPostalCode,
        string $addressLocality,
        string $numberPhone,
        ?string $numberFax,
        string $email,
        ?string $privacyResponsible = null,
        ?string $website = null,
        ?string $facebook = null,
        ?string $juvemWebsite = null,
        ?string $logoLink = null
    ) {
        $this->appTitle           = $appTitle ?: 'Juvem';
        $this->organizationName   = $organizationName;
        $this->addressStreet      = $addressStreet;
        $this->addressPostalCode  = $addressPostalCode;
        $this->addressLocality    = $addressLocality;
        $this->numberPhone        = $numberPhone;
        $this->numberFax          = $numberFax;
        $this->email              = $email;
        $this->website            = $website;
        $this->facebook           = $facebook;
        $this->juvemWebsite       = $juvemWebsite;
        $this->logoLink           = $logoLink;
        $this->privacyResponsible = $privacyResponsible;
    }


    /**
     * Access installation title
     *
     * @return  string  Html formatted text
     */
    public function title(): string
    {
        return $this->appTitle;
    }

    /**
     * Access installation organization name
     *
     * @return  string  Html formatted text
     */
    public function organizationName(): string
    {
        return $this->organizationName;
    }

    /**
     * Access organization address street
     *
     * @return string
     */
    public function organizationAddressStreet(): string
    {
        return $this->addressStreet;
    }

    /**
     * Access organization address postal code
     *
     * @return string
     */
    public function organizationAddressPostalCode(): string
    {
        return $this->addressPostalCode;
    }

    /**
     * Access organization address locality
     *
     * @return string
     */
    public function organizationAddressLocality(): string
    {
        return $this->addressLocality;
    }

    /**
     * Access organization phone number
     *
     * @return string
     */
    public function organizationNumberPhone(): string
    {
        return $this->numberPhone;
    }

    /**
     * Access organization fax number
     *
     * @return string|null
     */
    public function organizationNumberFax(): ?string
    {
        return $this->numberFax;
    }

    /**
     * Access organization e-mail
     *
     * @return string
     */
    public function organizationEmail(): string
    {
        return $this->email;
    }

    /**
     * Name of person responsible for data privacy
     *
     * @return string|null
     */
    public function privacyResponsible()
    {
        return $this->privacyResponsible;
    }


    /**
     * Access organization website
     *
     * @return  string|null  Website url
     */
    public function organizationWebsite()
    {
        return $this->website;
    }

    /**
     * Access organization website
     *
     * @return  string|null Facebook url part if any defined
     */
    public function organizationFacebook()
    {
        return $this->facebook;
    }

    /**
     * Get the juvem app base url
     *
     * @return null|string
     */
    public function juvemWebsite()
    {
        return $this->juvemWebsite;
    }

    /**
     * @return null|string
     */
    public function getLogoLink()
    {
        return $this->logoLink;
    }

}
