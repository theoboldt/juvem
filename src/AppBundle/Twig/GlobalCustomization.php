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

use \Twig\Environment;

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
     * Twig environment used for rendering
     *
     * @var Environment
     */
    protected $twig;

    /**
     * Contains applications root dir
     *
     * @var string
     */
    private $rootDir;

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
     * @var string|null
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
     * @var string|null
     */
    private $website;

    /**
     * Facebook link if available
     *
     * @var string|null
     */
    private $facebook;

    /**
     * Juvem base url
     *
     * @var string|null
     */
    private $juvemWebsite;

    /**
     * If configured, link of application logo will not redirect to homepage but to specified url
     *
     * @var string|null
     */
    private $logoLink;

    /**
     * Name of person responsible for data privacy
     *
     * @var null
     */
    private $privacyResponsible;

    /**
     * Customization constructor
     *
     * @param Environment $twig                    Twig environment used for rendering
     * @param string      $rootDir                 Applications root dir
     * @param string      $appTitle                Name of app
     * @param string      $organizationName        Name of organization
     * @param string      $addressStreet           Address of organization, street name
     * @param string      $addressPostalCode       Address of organization, postal code
     * @param string      $addressLocality         Address of organization, city
     * @param string      $numberPhone             Phone number of organization
     * @param string      $numberFax               Fax number of organization
     * @param string      $email                   E-mail address of organization
     * @param string|null $privacyResponsible      Name of person responsible for data privacy
     * @param string|null $website                 Website of organization
     * @param string|null $facebook                Facebook link if available
     * @param string|null $juvemWebsite            Juvem app website base url
     * @param string|null $logoLink                If configured, link of application logo will redirect to specified
     *                                             url
     */
    public function __construct(
        Environment $twig,
        string $rootDir,
        string $appTitle,
        string $organizationName,
        string $addressStreet,
        string $addressPostalCode,
        string $addressLocality,
        string $numberPhone,
        string $numberFax,
        string $email,
        ?string $privacyResponsible = null,
        ?string $website = null,
        ?string $facebook = null,
        ?string $juvemWebsite = null,
        ?string $logoLink = null
    ) {
        $this->twig               = $twig;
        $this->rootDir            = $rootDir;
        $this->appTitle           = $appTitle;
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
     * HTML markup for inline organization data
     *
     * @return string
     */
    public function organizationCardInline(): string
    {
        $formatPhoneNumber = function ($v) {
            return str_replace(['(', ' ', ')', '-', '/'], '', $v);
        };
        return $this->twig->render(
            'customization/organization-card-inline.html.twig',
            [
                'organizationName'  => $this->organizationName,
                'addressStreet'     => $this->addressStreet,
                'addressPostalCode' => $this->addressPostalCode,
                'addressLocality'   => $this->addressLocality,
                'numberPhone'       => $formatPhoneNumber($this->numberPhone),
                'numberFax'         => $formatPhoneNumber($this->numberFax),
                'email'             => $this->email,
                'website'           => $this->website
            ]
        );
    }

    /**
     * HTML markup for organization data
     *
     * @return string
     */
    public function organizationCard(): string
    {
        return $this->twig->render(
            'customization/organization-card.html.twig',
            [
                'organizationName'  => $this->organizationName,
                'addressStreet'     => $this->addressStreet,
                'addressPostalCode' => $this->addressPostalCode,
                'addressLocality'   => $this->addressLocality,
                'numberPhone'       => $this->numberPhone,
                'numberFax'         => $this->numberFax,
                'email'             => $this->email,
                'website'           => $this->website
            ]
        );
    }

    /**
     * HTML markup for privacy page organization data
     *
     * @return string
     */
    public function privacyCard(): string
    {
        return $this->twig->render(
            'customization/privacy-card.html.twig',
            [
                'organizationName'   => $this->organizationName,
                'addressStreet'      => $this->addressStreet,
                'addressPostalCode'  => $this->addressPostalCode,
                'addressLocality'    => $this->addressLocality,
                'numberPhone'        => $this->numberPhone,
                'numberFax'          => $this->numberFax,
                'email'              => $this->email,
                'website'            => $this->website,
                'privacyResponsible' => $this->privacyResponsible,
            ]
        );
    }

    /**
     * HTML markup for imprint page content
     *
     * @return string
     */
    public function legalImprintContent(): string
    {
        return $this->renderCustomizedIfAvailable('imprint-content');
    }

    /**
     * HTML markup for imprint page content
     *
     * @return string
     */
    public function legalConditionsOfTravelContent(): string
    {
        return $this->renderCustomizedIfAvailable('conditions-of-travel-content');
    }

    /**
     * HTML markup for imprint page content
     *
     * @return string
     */
    public function legalConditionsOfTravelScrollspy(): string
    {
        return $this->renderCustomizedIfAvailable('conditions-of-travel-scrollspy');
    }

    /**
     * HTML markup for corona page content
     *
     * @return string
     */
    public function legalConditionsCoronaContent(): string
    {
        return $this->renderCustomizedIfAvailable('conditions-corona-content');
    }

    /**
     * HTML markup for corona page content
     *
     * @return string
     */
    public function legalConditionsCoronaScrollspy(): string
    {
        return $this->renderCustomizedIfAvailable('conditions-corona-scrollspy');
    }

    /**
     * Render defined template, use customized override in config folder if defined
     *
     * @see isCustomizationAvailable()
     * @see customizedTemplatePath()
     * @param   string $template Template base name
     * @return  string
     */
    protected function renderCustomizedIfAvailable(string $template): string
    {
        if (self::isCustomizationAvailable($template)) {
            return $this->twig->render('@customization/'.$template.'.html.twig');
        } else {
            return $this->twig->render('legal/' . $template . '.html.twig');
        }
    }

    /**
     * Generates path to customized template
     *
     * @param  string $template Template base name
     * @return string
     */
    public static function customizedTemplatePath(string $template): string
    {
        $template = str_replace('/\\', '', $template);
        return __DIR__ . '/../../../config/templates/' . $template . '.html.twig';
    }

    /**
     * Find out if there is a customization for defined template available
     *
     * @see customizedTemplatePath()
     * @param  string $template Template base name
     * @return boolean
     */
    public static function isCustomizationAvailable(string $template): bool
    {
        $customizedTemplate = self::customizedTemplatePath($template);
        return (file_exists($customizedTemplate) && is_readable($customizedTemplate));
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
    public function organizationNumberFax()
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
