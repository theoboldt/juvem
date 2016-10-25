<?php
namespace AppBundle\Controller\Newsletter;


use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Entity\User;
use AppBundle\Form\NewsletterMailType;
use AppBundle\Form\NewsletterSubscriptionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class AdminController extends Controller
{

    /**
     * Page for details of an event
     *
     * @Route("/admin/newsletter", name="newsletter_admin_overview")
     */
    public function overview(Request $request)
    {
        return $this->render(
            'newsletter/admin/overview.html.twig'
        );
    }

    /**
     * Page for details of an event
     *
     * @Route("/admin/newsletter/list", name="newsletter_admin_list")
     */
    public function listSubscriptions(Request $request)
    {
        return $this->render(
            'newsletter/admin/list.html.twig'
        );
    }

    /**
     * Page for details of an event
     *
     * @Route("/admin/newsletter/subscription/{rid}", requirements={"id": "\d"},
     *                                       name="newsletter_subscription_detail")
     */
    public function subscriptionDetail(Request $request)
    {
        $rid        = $request->get('rid');
        $repository = $this->getDoctrine()->getRepository('AppBundle:NewsletterSubscription');

        /** @var NewsletterSubscription $subscription */
        $subscription = $repository->findOneBy(array('rid' => $rid));
        $form         = $this->createForm(NewsletterSubscriptionType::class, $subscription);

        $form->handleRequest($request);
        if ($form->isValid() && $form->isSubmitted() && $subscription) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($subscription);
            $em->flush();

            $this->addFlash(
                'success',
                'Die Änderungen des Newsletter-Abonnements wurden gespeichert'
            );
        }

        return $this->render(
            'newsletter/admin/details.html.twig',
            array('form' => $form->createView(), 'subscription' => $subscription)
        );
    }

    /**
     * Page for details of an event
     *
     * @Route("/admin/newsletter/list.json", name="newsletter_admin_list_data")
     */
    public function listSubscriptionsData(Request $request)
    {
        $repository             = $this->getDoctrine()->getRepository('AppBundle:NewsletterSubscription');
        $subscriptionEntityList = $repository->findAll();
        $subscriptionList       = array();


        foreach ($subscriptionEntityList as $subscription) {
            $ageRangeBegin = $subscription->getAgeRangeBegin();
            $ageRangeEnd   = $subscription->getAgeRangeEnd();
            $userContent   = null;
            $user          = $subscription->getAssignedUser();
            if ($user) {
                $userContent = sprintf(
                    '<a href="%s">%s</a>',
                    $this->generateUrl('user_detail', array('uid' => $user->getUid())),
                    User::fullname($user->getNameLast(), $user->getNameFirst())
                );
            }

            $subscriptionList[] = array(
                'rid'           => $subscription->getRid(),
                'email'         => $subscription->getEmail(),
                'user'          => $userContent,
                'ageRangeBegin' => $ageRangeBegin,
                'ageRangeEnd'   => $ageRangeEnd,
                'ageRange'      => $this->renderView(
                    'newsletter/admin/age-range-progress.html.twig',
                    array(
                        'ageRangeBegin' => $ageRangeBegin,
                        'ageRangeEnd'   => $ageRangeEnd,
                        'ageRangeMin'   => NewsletterSubscription::AGE_RANGE_MIN,
                        'ageRangeMax'   => NewsletterSubscription::AGE_RANGE_MAX,
                    )
                )
            );
        }
        return new JsonResponse($subscriptionList);
    }

    /**
     * Page for details of an event
     *
     * @Route("/admin/newsletter/send", name="newsletter_admin_send")
     */
    public function sendNewsletter(Request $request)
    {
        $form = $this->createForm(NewsletterMailType::class);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $this->addFlash(
                'info',
                'Die Benachrichtigungs-Emails wurden versandt'
            );

            return $this->redirectToRoute('newsletter_admin_overview');
        }

        return $this->render(
            'newsletter/admin/send.html.twig', array('form' => $form->createView())
        );
    }

}