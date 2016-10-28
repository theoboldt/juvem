<?php

namespace AppBundle\Controller;

use AppBundle\BitMask\LabelFormatter;
use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\Participant;
use AppBundle\Entity\User;
use AppBundle\Form\UserRoleAssignmentType;
use AppBundle\Twig\Extension\BootstrapGlyph;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller
{
    /**
     * @Route("/admin/user/list", name="user_list")
     * @Security("has_role('ROLE_ADMIN_USER')")
     */
    public function listAction(Request $request)
    {
        return $this->render('user/list.html.twig');
    }

    /**
     * Data provider for event list grid
     *
     * @Route("/admin/user/list.json", name="user_list_data")
     * @Security("has_role('ROLE_ADMIN_USER')")
     */
    public function listDataAction(Request $request)
    {
        $glyph = new BootstrapGlyph();

        $userManager  = $this->container->get('fos_user.user_manager');
        $entityList   = $userManager->findUsers();
        $roleTemplate = ' <span title="%s">%s</span>';

        $userList = array();

        /** @var User $entity */
        foreach ($entityList as $entity) {
            $entityRoles = $entity->getRoles();

            $roles = '';
            if (in_array('ROLE_USER', $entityRoles)) {
                $roles .= sprintf($roleTemplate, 'Benutzer', $glyph->bootstrapGlyph('pawn'));
            }
            if (in_array(User::ROLE_ADMIN, $entityRoles)) {
                $roles .= sprintf($roleTemplate, User::ROLE_ADMIN_LABEL, $glyph->bootstrapGlyph('king'));
            }
            if (in_array(User::ROLE_ADMIN_USER, $entityRoles)) {
                $roles .= sprintf($roleTemplate, User::ROLE_ADMIN_USER_LABEL, $glyph->bootstrapGlyph('queen'));
            }
            if (in_array(User::ROLE_ADMIN_EVENT, $entityRoles)) {
                $roles .= sprintf($roleTemplate, User::ROLE_ADMIN_EVENT_LABEL, $glyph->bootstrapGlyph('bishop'));
            }
            if (in_array(User::ROLE_ADMIN_NEWSLETTER, $entityRoles)) {
                $roles .= sprintf($roleTemplate, User::ROLE_ADMIN_NEWSLETTER_LABEL, $glyph->bootstrapGlyph('knight'));
            }


            $userList[] = array(
                'uid'       => $entity->getUid(),
                'email'     => $entity->getEmail(),
                'nameFirst' => $entity->getNameFirst(),
                'nameLast'  => $entity->getNameLast(),
                'roles'     => $roles
            );
        }

        return new JsonResponse($userList);
    }

    /**
     * @Route("/admin/user/{uid}", requirements={"uid": "\d+"}, name="user_detail")
     * @Security("has_role('ROLE_ADMIN_USER')")
     */
    public function userDetailAction(Request $request)
    {
        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:User');

        $user = $repository->findOneBy(array('id' => $request->get('uid')));

        $form = $this->createForm(
            UserRoleAssignmentType::class,
            array('uid'  => $user->getUid(),
                  'role' => $user->getRoles()
            )
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();

            $roleList = $form->get('role')->getData();
            $user->setRoles($roleList);
            $em->persist($user);
            $em->flush();
        }

        return $this->render(
            'user/detail.html.twig', array('user'       => $user,
                                           'userIsSelf' => ($user->getUid() == $this->getUser()->getUid()),
                                           'form'       => $form->createView()
                                   )
        );
    }


    /**
     * Data provider for events participants list grid
     *
     * @Route("/admin/user/{uid}/participations.json", requirements={"uid": "\d+"},
     *                                                 name="admin_user_participations_list_data")
     * @Security("has_role('ROLE_ADMIN_USER')")
     */
    public function listParticipantsDataAction(Request $request)
    {
        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(
            ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED
        );

        $userRepository = $this->getDoctrine()
                               ->getRepository('AppBundle:User');
        $user           = $userRepository->findOneBy(array('id' => $request->get('uid')));

        $participationRepository = $this->getDoctrine()
                                        ->getRepository('AppBundle:Participation');
        $participationList       = $participationRepository->findBy(array('assignedUser' => $user->getUid()));

        $participationListResult = array();
        /** @var Participant $participant */
        foreach ($participationList as $participation) {
            $event = $participation->getEvent();

            $participants = array();
            foreach ($participation->getParticipants() as $participant) {
                $participants[] = $participant->getNameFirst();
            }

            $participationListResult[] = array(
                'eid'          => $event->getEid(),
                'pid'          => $participation->getPid(),
                'eventTitle'   => $event->getTitle(),
                'participants' => implode(', ', $participants)
            );
        }

        return new JsonResponse($participationListResult);
    }

}