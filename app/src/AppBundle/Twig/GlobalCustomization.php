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
class GlobalCustomization extends GlobalCustomizationConfiguration
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
     * Customization constructor
     *
     * @param Environment $twig               Twig environment used for rendering
     * @param string      $rootDir            Applications root dir
     * @param string|null $appTitle           Name of app
     * @param string      $organizationName   Name of organization
     * @param string      $addressStreet      Address of organization, street name
     * @param string      $addressPostalCode  Address of organization, postal code
     * @param string      $addressLocality    Address of organization, city
     * @param string      $numberPhone        Phone number of organization
     * @param string|null $numberFax          Fax number of organization
     * @param string      $email              E-mail address of organization
     * @param string|null $privacyResponsible Name of person responsible for data privacy
     * @param string|null $website            Website of organization
     * @param string|null $facebook           Facebook link if available
     * @param string|null $juvemWebsite       Juvem app website base url
     * @param string|null $logoLink           If configured, link of application logo will redirect to specified url
     */
    public function __construct(
        Environment $twig,
        string $rootDir,
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
        $this->twig    = $twig;
        $this->rootDir = $rootDir;

        parent::__construct(
            $appTitle,
            $organizationName,
            $addressStreet,
            $addressPostalCode,
            $addressLocality,
            $numberPhone,
            $numberFax,
            $email,
            $privacyResponsible,
            $website,
            $facebook,
            $juvemWebsite,
            $logoLink
        );
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
        return __DIR__ . '/../../../../var/config/templates/' . $template . '.html.twig';
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


}
