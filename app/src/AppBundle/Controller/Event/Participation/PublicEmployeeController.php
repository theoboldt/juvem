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

use AppBundle\Controller\AuthorizationAwareControllerTrait;
use AppBundle\Controller\DoctrineAwareControllerTrait;
use AppBundle\Controller\FlashBagAwareControllerTrait;
use AppBundle\Controller\FormAwareControllerTrait;
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Controller\RoutingControllerTrait;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Form\EmployeeType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class PublicEmployeeController
{
    use RoutingControllerTrait, AuthorizationAwareControllerTrait, FormAwareControllerTrait, RenderingControllerTrait, DoctrineAwareControllerTrait, FlashBagAwareControllerTrait, RoutingControllerTrait;

    /**
     * AdminController constructor.
     *
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     * @param ManagerRegistry               $doctrine
     * @param RouterInterface               $router
     * @param Environment                   $twig
     * @param FormFactoryInterface          $formFactory
     * @param SessionInterface              $session
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface         $tokenStorage,
        ManagerRegistry               $doctrine,
        RouterInterface               $router,
        Environment                   $twig,
        FormFactoryInterface          $formFactory,
        SessionInterface              $session
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage         = $tokenStorage;
        $this->doctrine             = $doctrine;
        $this->router               = $router;
        $this->twig                 = $twig;
        $this->formFactory          = $formFactory;
        $this->session              = $session;
    }

    /**
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/employee/register", requirements={"eid": "\d+"}, name="event_public_employee_register")
     * @param Event   $event
     * @param Request $request
     * @return mixed
     */
    public function createBeginAction(Event $event, Request $request): Response
    {
        $eid = $event->getEid();

        if (!$event->getIsActiveRegistrationEmployee()) {
            $this->addFlash(
                'danger',
                'Für die Veranstaltung <i>' . htmlspecialchars($event->getTitle()) .
                '</i> werden im Moment keine Mitarbeitenden-Anmeldungen erfasst'
            );

            return $this->redirectToRoute('homepage');
        }
        $employee = new Employee($event);
        $employee->setIsConfirmed(false);
        $employee->addPhoneNumber(new PhoneNumber());

        /** @var \AppBundle\Entity\User $user */
        $user = $this->getUser();
        if ($user) {
            $employee->setNameLast($user->getNameLast());
            $employee->setNameFirst($user->getNameFirst());
        }

        $form = $this->createForm(
            EmployeeType::class,
            $employee,
            [
                EmployeeType::ACQUISITION_FIELD_PUBLIC  => true,
                EmployeeType::ACQUISITION_FIELD_PRIVATE => false,
                EmployeeType::DISCLAIMER_FIELDS         => true,
            ]
        );

        if ($request->request->has('employee')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $employeeData = $request->request->get('employee', null);
                $request->getSession()->set('employee-data-' . $eid, $employeeData);

                return $this->redirectToRoute('event_public_employee_confirm', ['eid' => $eid]);
            }
        } elseif ($request->getSession()->get('employee-data-' . $eid, null)) {
            $employeeData = $request->getSession()->get('employee-data-' . $eid);
            $form->submit($employeeData);
            $employee->setEvent($event);
        }

        return $this->render(
            'event/employee/public/begin.html.twig',
            [
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(false, false, true, false, true),
                'form'              => $form->createView(),
            ]
        );
    }

    /**
     * Page summary and confirmation
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/event/{eid}/employee/confirm", requirements={"eid": "\d+"}, name="event_public_employee_confirm")
     */
    public function createConfirmAction(Event $event, Request $request): Response
    {
        $eid          = $event->getEid();
        $employeeData = $request->getSession()->get('employee-data-' . $eid, null);
        if (!$employeeData) {
            return $this->redirectToRoute('event_public_employee_register', ['eid' => $eid]);
        }
        $employee = new Employee($event);
        $employee->setIsConfirmed(false);

        /** @var \AppBundle\Entity\User $user */
        $user = $this->getUser();
        if ($user) {
            $employee->setNameLast($user->getNameLast());
            $employee->setNameFirst($user->getNameFirst());
        }

        $form = $this->createForm(
            EmployeeType::class,
            $employee,
            [
                EmployeeType::ACQUISITION_FIELD_PUBLIC  => true,
                EmployeeType::ACQUISITION_FIELD_PRIVATE => false,
                EmployeeType::DISCLAIMER_FIELDS         => true,
            ]
        );

        $form->submit($employeeData);
        if ($request->query->has('confirm')) {
            if (!$form->isSubmitted() || !$form->isValid()) {
                return $this->redirectToRoute('event_public_employee_register', ['eid' => $eid]);
            }

            $request->getSession()->remove('employee-data-' . $eid);

            $em = $this->getDoctrine()->getManager();
            $em->persist($employee);
            $em->flush();

            return $this->redirectToRoute('event_public_detail', ['eid' => $eid]);
        } else {
            if ($employee->getAddressStreetNumber() === null) {
                $this->addFlash(
                    'warning',
                    sprintf(
                        'Es wurde keine Hausnummer erkannt. Falls Ihre Eingabe <code>%s</code> für Straße <b>und</b> Hausnummer unvollständig ist, <a href="%s#participation-address">vervollständigen Sie bitte die Angabe</a>.',
                        htmlentities($employee->getAddressStreet()),
                        $this->router->generate('event_public_employee_register', ['eid' => $eid])
                    )
                );
            }

            return $this->render(
                'event/employee/public/confirm.html.twig',
                [
                    'employee' => $employee,
                    'event'    => $event,
                ]
            );
        }
    }
}
