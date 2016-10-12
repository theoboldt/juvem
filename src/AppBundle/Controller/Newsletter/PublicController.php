<?php
namespace AppBundle\Controller\Newsletter;


use AppBundle\Entity\NewsletterRecipient;
use AppBundle\Entity\User;
use AppBundle\Form\NewsletterRecipientType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class PublicController extends Controller
{
    /**
     * Page for details of an event
     *
     * @Route("/newsletter", name="public_newsletter")
     */
    public function newsletterSettings()
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            $newsletterRecipient = $user->getAssignedNewsletter();
            if (!$newsletterRecipient) {
                $newsletterRecipient    = new NewsletterRecipient();
                $newsletterRecipient->setEmail($user->getEmail());
            }
        } else {
            $newsletterRecipient = null;
        }

        $form                = $this->createForm(NewsletterRecipientType::class, $newsletterRecipient);

        return $this->render(
            'newsletter/public/settings.html.twig', array(
                                                      'form' => $form->createView()

                                                  )
        );
    }
}