<?php
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
     * Create new mail generator
     *
     * @param Twig_Environment $twig Twig environment used for rendering
     */
    public function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
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
        /** @var Twig_Template $template */
        $template = $this->twig->loadTemplate($path); // Define your own schema

        $subject  = $template->renderBlock('subject', $parameters);
        $bodyHtml = $template->renderBlock('body_html', $parameters);
        $bodyText = $template->renderBlock('body_text', $parameters);

        return Swift_Message::newInstance()
                            ->setFrom('jungschar.vaihingen@gmail.com', 'Juvem - Jugendwerk S-Vaihingen')
                            ->setSubject($subject)
                            ->setBody($bodyText, 'text/plain')
                            ->addPart($bodyHtml, 'text/html');
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