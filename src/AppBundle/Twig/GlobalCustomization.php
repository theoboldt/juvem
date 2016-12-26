<?php

namespace AppBundle\Twig;

/**
 * Twig global for placing installation based app names and legal notices
 *
 * Class Customization
 *
 * @package AppBundle\Twig\Extension
 */
class GlobalCustomization
{
    /**
     * Name of app
     *
     * @var string
     */
    private $appTitle;

    /**
     * Name of organization
     *
     * @var string
     */
    private $organizationName;

    /**
     * Address of organization, street name
     *
     * @var string
     */
    private $addressStreet;

    /**
     * Address of organization, postal code
     *
     * @var string
     */
    private $addressPostalCode;

    /**
     * Address of organization, postal code
     *
     * @var string
     */
    private $addressLocality;

    /**
     * Phone number of organization
     *
     * @var string
     */
    private $numberPhone;

    /**
     * Fax number of organization
     *
     * @var string
     */
    private $numberFax;

    /**
     * E-mail address of organization
     *
     * @var string
     */
    private $email;

    /**
     * Website of organization
     *
     * @var string
     */
    private $website;

    /**
     * Customization constructor
     *
     * @param string $appTitle          Name of app
     * @param string $organizationName  Name of organization
     * @param string $addressStreet     Address of organization, street name
     * @param string $addressPostalCode Address of organization, postal code
     * @param string $addressLocality   Address of organization, city
     * @param string $numberPhone       Phone number of organization
     * @param string $numberFax         Fax number of organization
     * @param string $email             E-mail address of organization
     * @param string $website           Website of organization
     */
    public function __construct(
        $appTitle, $organizationName, $addressStreet, $addressPostalCode, $addressLocality, $numberPhone, $numberFax,
        $email, $website
    )
    {
        $this->appTitle          = $appTitle;
        $this->organizationName  = $organizationName;
        $this->addressStreet     = $addressStreet;
        $this->addressPostalCode = $addressPostalCode;
        $this->addressLocality   = $addressLocality;
        $this->numberPhone       = $numberPhone;
        $this->numberFax         = $numberFax;
        $this->email             = $email;
        $this->website           = $website;
    }

    /**
     * Access installation title
     *
     * @return  string  Html formatted text
     */
    public function title()
    {
        return $this->appTitle;
    }

    /**
     * Access installation organization name
     *
     * @return  string  Html formatted text
     */
    public function organizationName()
    {
        return $this->organizationName;
    }

    /**
     * HTML markup for inline organization data
     *
     * @return string
     */
    public function organizationCardInline()
    {
        $formatPhoneNumber = function ($v) {
            return str_replace(['(', ' ', ')', '-', '/'], '', $v);
        };

        return sprintf(
            '<span itemscope itemtype="http://schema.org/Organization">
                    <i itemprop="name">%s</i>,
                    <span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">
                        <span itemprop="streetAddress">%s</span>,
                        <span itemprop="postalCode">%s</span> <span itemprop="addressLocality">%s</span>
                    </span>
                    (Telefon: <span itemprop="telephone">%s</span>,
                    Telefax: <span itemprop="faxNumber">%s</span>,
                    E-Mail: <span itemprop="email">%s</span>, 
                    Website: <a itemprop="url" href="http:/%s/" target="_blank">%s</a>)
            </span>',
            $this->organizationName,
            $this->addressStreet,
            $this->addressPostalCode,
            $this->addressLocality,
            $formatPhoneNumber($this->numberPhone),
            $formatPhoneNumber($this->numberFax),
            $this->email,
            $this->website,
            $this->website
        );
    }

    /**
     * HTML markup for organization data
     *
     * @return string
     */
    public function organizationCard()
    {
        return sprintf('
            <div itemscope itemtype="http://schema.org/Organization">
                <address>
                    <strong itemprop="name">%s</strong><br>

                    <div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">
                        <span itemprop="streetAddress">%s</span><br>
                        <span itemprop="postalCode">%s</span> <span itemprop="addressLocality">%s</span><br>

                    </div>
                    <span class="glyphicon glyphicon-phone-alt" aria-hidden="true"></span> <span itemprop="telephone">%s</span><br>
                    <span class="glyphicon glyphicon-print" aria-hidden="true"></span> <span itemprop="faxNumber">%s</span><br>
                    <span class="glyphicon glyphicon-envelope" aria-hidden="true"></span> <span itemprop="email">%s</span>
                    <span class="glyphicon glyphicon-globe" aria-hidden="true"></span> <a itemprop="url" href="http:/%s/" target="_blank">%s</a>
                </address>
            </div>',
            $this->organizationName,
            $this->addressStreet,
            $this->addressPostalCode,
            $this->addressLocality,
            $this->numberPhone,
            $this->numberFax,
            $this->email,
            $this->website,
            $this->website
        );
    }

    /**
     * Access privacy notice
     *
     * @return  string  Html formatted text
     */
    public function legalPrivacyNotice()
    {
        return 'AGB';
    }

    /**
     * Access privacy notice
     *
     * @return  string  Html formatted text
     */
    public function legalConditionsOfTravel()
    {
        return 'RGB';
    }
}
