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


interface GlobalCustomizationConfigurationProvider
{
    
    /**
     * Access installation title
     *
     * @return  string  Html formatted text
     */
    public function title(): string;

    /**
     * Access installation organization name
     *
     * @return  string  Html formatted text
     */
    public function organizationName(): string;

    /**
     * Access organization address street
     *
     * @return string
     */
    public function organizationAddressStreet(): string;

    /**
     * Access organization address postal code
     *
     * @return string
     */
    public function organizationAddressPostalCode(): string;

    /**
     * Access organization address locality
     *
     * @return string
     */
    public function organizationAddressLocality(): string;

    /**
     * Access organization phone number
     *
     * @return string
     */
    public function organizationNumberPhone(): string;

    /**
     * Access organization fax number
     *
     * @return string|null
     */
    public function organizationNumberFax(): ?string;

    /**
     * Access organization e-mail
     *
     * @return string
     */
    public function organizationEmail(): string;

    /**
     * Name of person responsible for data privacy
     *
     * @return string|null
     */
    public function privacyResponsible();


    /**
     * Access organization website
     *
     * @return  string|null  Website url
     */
    public function organizationWebsite();

    /**
     * Access organization website
     *
     * @return  string|null Facebook url part if any defined
     */
    public function organizationFacebook();

    /**
     * Get the juvem app base url
     *
     * @return null|string
     */
    public function juvemWebsite();

    /**
     * @return null|string
     */
    public function getLogoLink();
}
