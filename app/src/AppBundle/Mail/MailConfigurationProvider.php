<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Mail;


use AppBundle\Twig\GlobalCustomization;

class MailConfigurationProvider
{
    private string $mailerImapHost;
    
    private string $mailerUser;
    
    private string $mailerPassword;
    
    /**
     * mailer_address
     *
     * @var string
     */
    private string $mailerAddress;
    
    /**
     * @var GlobalCustomization
     */
    private GlobalCustomization $customization;
    
    /**
     * MailConfigurationProvider constructor.
     *
     * @param string $mailerHost
     * @param string|null $mailerImapHost
     * @param string $mailerUser
     * @param string $mailerPassword
     * @param string $mailerAddress
     * @param GlobalCustomization $customization
     */
    public function __construct(
        string $mailerHost,
        ?string $mailerImapHost,
        string $mailerUser,
        string $mailerPassword,
        string $mailerAddress,
        GlobalCustomization $customization
    )
    {
        $this->mailerImapHost = $mailerImapHost ?: $mailerHost;
        $this->mailerUser     = $mailerUser;
        $this->mailerPassword = $mailerPassword;
        $this->mailerAddress  = $mailerAddress;
        $this->customization  = $customization;
    }
    
    /**
     * @return string
     */
    public function getMailerImapHost(): string
    {
        return $this->mailerImapHost;
    }
    
    /**
     * @return string
     */
    public function getMailerUser(): string
    {
        return $this->mailerUser;
    }
    
    /**
     * @return string
     */
    public function getMailerPassword(): string
    {
        return $this->mailerPassword;
    }
    
    /**
     * @return string
     */
    public function getMailerAddress(): string
    {
        return $this->mailerAddress;
    }
    
    /**
     * Access installation organization name
     *
     * @return  string  Html formatted text
     */
    public function organizationName(): string
    {
        return $this->customization->organizationName();
    }
    
    /**
     * Access organization e-mail
     *
     * @return string
     */
    public function organizationEmail(): string
    {
        return $this->customization->organizationEmail();
    }
    
}
