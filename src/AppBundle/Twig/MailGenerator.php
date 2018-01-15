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

use Swift_Message;
use Twig_Environment;
use Twig_Template;

class MailGenerator
{
    /**
     * Twig environment used for rendering
     *
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * Customization provider service
     *
     * @var GlobalCustomization
     */
    protected $customization;

    /**
     * The e-mail address used for the "from" field of emails
     *
     * @var string
     */
    private $mailerAddress;

    /**
     * Create new mail generator
     *
     * @param string              $mailerAddress The e-mail address used for the "from" field of emails
     * @param Twig_Environment    $twig          Twig environment used for rendering
     * @param GlobalCustomization $customization Customization provider service
     */
    public function __construct($mailerAddress, Twig_Environment $twig, GlobalCustomization $customization)
    {
        $this->mailerAddress = $mailerAddress;
        $this->twig          = $twig;
        $this->customization = $customization;
    }

    /**
     * Create a swift message by using txt and html template
     *
     * @param  string $path       Full path to twig template
     * @param  array  $parameters Parameters for template
     * @return Swift_Message      The email
     */
    public function getMessageByPath($path, $parameters = [])
    {
        if (!isset($parameters['customization'])) {
            $parameters['customization']    = $this->customization;
        }
        $organizationEmail = $this->customization->organizationEmail();
        $organizationName  = $this->customization->organizationName();
        $appTitle          = $this->customization->title();

        /** @var Twig_Template $template */
        $template = $this->twig->loadTemplate($path); // Define your own schema

        $subject  = $template->renderBlock('subject', $parameters);
        $bodyHtml = $template->renderBlock('body_html', $parameters);
        $bodyText = $template->renderBlock('body_text', $parameters);

        $senderName = $organizationName . ' [' . $appTitle . ']';
        $message    = (new Swift_Message())
            ->setFrom($this->mailerAddress, $senderName)
            ->setSender($this->mailerAddress, $senderName)
            ->setSubject($subject);

        if ($organizationEmail) {
            $message->setReplyTo($organizationEmail, $organizationName)
                    ->setReturnPath($organizationEmail);
        }
        $message->setBody($bodyText, 'text/plain')
                ->addPart($bodyHtml, 'text/html');

        return $message;
    }

    /**
     * Create a swift message by using identifier
     *
     * @param  string $identifier Twig template identifier
     * @param  array  $parameters Parameters for template
     * @return Swift_Message      The email
     */
    public function getMessage($identifier, $parameters = [])
    {
        return $this->getMessageByPath('mail/' . $identifier . '.email.twig', $parameters);
    }
}