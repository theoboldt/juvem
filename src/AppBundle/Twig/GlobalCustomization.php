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
     * HTML markup for impressum page content
     *
     * @return string
     */
    public function legalImpressumContent()
    {
        $customizedImpressumPage = $this->rootDir . '/config/impressum-content.html.twig';
        if (file_exists($customizedImpressumPage) && is_readable($customizedImpressumPage)) {
            return $this->twig->render($customizedImpressumPage);
        } else {
            return $this->twig->render('legal/impressum-content-default.html.twig');
        }
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
