<?php

namespace AppBundle\Twig;

use Twig_Environment;

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
     * @var Twig_Environment
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
     * @param Twig_Environment $twig              Twig environment used for rendering
     * @param string           $rootDir           Applications root dir
     * @param string           $appTitle          Name of app
     * @param string           $organizationName  Name of organization
     * @param string           $addressStreet     Address of organization, street name
     * @param string           $addressPostalCode Address of organization, postal code
     * @param string           $addressLocality   Address of organization, city
     * @param string           $numberPhone       Phone number of organization
     * @param string           $numberFax         Fax number of organization
     * @param string           $email             E-mail address of organization
     * @param string           $website           Website of organization
     */
    public function __construct(
        Twig_Environment $twig, $rootDir, $appTitle, $organizationName, $addressStreet, $addressPostalCode,
        $addressLocality, $numberPhone, $numberFax, $email, $website
    )
    {
        $this->twig              = $twig;
        $this->rootDir           = $rootDir;
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
     * HTML markup for inline organization data
     *
     * @return string
     */
    public function organizationCardInline()
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
    public function organizationCard()
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
     * HTML markup for imprint page content
     *
     * @return string
     */
    public function legalImprintContent()
    {
        return $this->renderCustomizedIfAvailable('imprint-content');
    }

    /**
     * HTML markup for imprint page content
     *
     * @return string
     */
    public function legalConditionsOfTravelContent()
    {
        return $this->renderCustomizedIfAvailable('conditions-of-travel-content');
    }

    /**
     * HTML markup for imprint page content
     *
     * @return string
     */
    public function legalConditionsOfTravelScrollspy()
    {
        return $this->renderCustomizedIfAvailable('conditions-of-travel-scrollspy');
    }

    /**
     * Render defined template, use customized override in config folder if defined
     *
     * @see isCustomizationAvailable()
     * @see customizedTemplatePath()
     * @param   string $template Template base name
     * @return  string
     */
    protected function renderCustomizedIfAvailable($template)
    {
        $customizedTemplate = self::customizedTemplatePath($this->rootDir, $template);
        if (self::isCustomizationAvailable($this->rootDir, $template)) {
            return $this->twig->render($customizedTemplate);
        } else {
            return $this->twig->render('legal/' . $template . '.html.twig');
        }
    }

    /**
     * Generates path to customized template
     *
     * @param  string $rootDir  The root dir of application
     * @param  string $template Template base name
     * @return string
     */
    public static function customizedTemplatePath($rootDir, $template)
    {
        return $rootDir . '/config/' . $template . '.html.twig';
    }

    /**
     * Find out if there is a customization for defined template available
     *
     * @see customizedTemplatePath()
     * @param  string $rootDir  The root dir of application
     * @param  string $template Template base name
     * @return boolean
     */
    public static function isCustomizationAvailable($rootDir, $template)
    {
        $customizedTemplate = self::customizedTemplatePath($rootDir, $template);
        return (file_exists($customizedTemplate) && is_readable($customizedTemplate));
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
     * Access organization website
     *
     * @return  string  Website url
     */
    public function organizationWebsite() {
        return $this->website;
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
     * @return string
     */
    public function organizationNumberFax(): string
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



}
