<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Event\Participation;

use AppBundle\Audit\AuditProvider;
use AppBundle\Controller\AuthorizationAwareControllerTrait;
use AppBundle\Controller\FormAwareControllerTrait;
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Entity\Event;
use AppBundle\Http\Annotation\CloseSessionEarly;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class AdminAuditController
{
    use AuthorizationAwareControllerTrait, FormAwareControllerTrait, RenderingControllerTrait;

    private AuditProvider $auditProvider;

    /**
     * AdminSingleController constructor.
     *
     * @param Environment                   $twig
     * @param RouterInterface               $router
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     * @param FormFactoryInterface          $formFactory
     */
    public function __construct(
        AuditProvider                 $auditProvider,
        Environment                   $twig,
        RouterInterface               $router,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface         $tokenStorage,
        FormFactoryInterface          $formFactory
    ) {
        $this->auditProvider        = $auditProvider;
        $this->twig                 = $twig;
        $this->router               = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage         = $tokenStorage;
        $this->formFactory          = $formFactory;
    }


    /**
     * Present all changes happened to entities related to transmitted event
     *
     * @CloseSessionEarly
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/changes", requirements={"eid": "\d+"}, name="event_participants_changes")
     * @Security("is_granted('participants_read', event)")
     */
    public function showEventRelatedChangesAction(Event $event, Request $request)
    {
        $date = new \DateTimeImmutable('yesterday noon');
        $form = $this->createFormBuilder()
                     ->add(
                         'date',
                         DateType::class,
                         [
                             'label'  => 'Datum',
                             'years'  => range((int)Date('Y') - 1, (int)Date('Y') + 1),
                             'widget' => 'single_text',
                             'format' => 'yyyy-MM-dd',
                             'data'   => $date,
                         ]
                     )
                     ->getForm();
        $form->handleRequest($request);

        $auditEvents = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $date = $form->get('date')->getData();
            if ($date instanceof \DateTime) {
                $date->setTime(0, 0, 0);
            } elseif ($date instanceof \DateTimeImmutable) {
                $date = $date->setTime(0, 0, 0);
            }

            if ($date) {
                $auditEvents = $this->auditProvider->provideAuditEvents($event, $date);
            }
        }
        return $this->render(
            'event/admin/track-changes.html.twig',
            [
                'event'       => $event,
                'date'        => $date,
                'auditEvents' => $auditEvents,
                'form'        => $form->createView(),
            ]
        );
    }
}
