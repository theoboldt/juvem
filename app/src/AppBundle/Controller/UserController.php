<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use AppBundle\Form\UserRoleAssignmentType;
use AppBundle\Twig\Extension\BootstrapGlyph;
use Doctrine\Persistence\ManagerRegistry;
use FOS\UserBundle\Doctrine\UserManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class UserController
{
    use RenderingControllerTrait, DoctrineAwareControllerTrait, FormAwareControllerTrait, AuthorizationAwareControllerTrait;

    /**
     * @var UserManager
     */
    private UserManager $fosUserManager;

    /**
     * UserController constructor.
     *
     * @param UserManager                   $fosUserManager
     * @param Environment                   $twig
     * @param FormFactoryInterface          $formFactory
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     * @param ManagerRegistry               $doctrine
     */
    public function __construct(
        UserManager $fosUserManager,
        Environment $twig,
        FormFactoryInterface $formFactory,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        ManagerRegistry $doctrine
    ) {
        $this->fosUserManager       = $fosUserManager;
        $this->twig                 = $twig;
        $this->formFactory          = $formFactory;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage         = $tokenStorage;
        $this->doctrine             = $doctrine;
    }

    /**
     * @Route("/admin/user/list", name="user_list")
     * @Security("is_granted('ROLE_ADMIN_USER')")
     */
    public function listAction(Request $request)
    {
        return $this->render('user/list.html.twig');
    }

    /**
     * Data provider for event list grid
     *
     * @Route("/admin/user/list.json", name="user_list_data")
     * @Security("is_granted('ROLE_ADMIN_USER')")
     */
    public function listDataAction(Request $request)
    {
        $glyph = new BootstrapGlyph();

        $userManager  = $this->fosUserManager;
        $entityList   = $userManager->findUsers();
        $roleTemplate = ' <span title="%s">%s</span>';

        $userList = [];

        /** @var User $entity */
        foreach ($entityList as $entity) {
            $entityRoles = $entity->getRoles();
    
            $roles = '';
            if (!in_array('ROLE_USER', $entityRoles)) {
                $roles .= sprintf($roleTemplate, 'Benutzer', $glyph->bootstrapGlyph('warning-sign'));
            }
            if (in_array(User::ROLE_ADMIN, $entityRoles)) {
                $roles .= sprintf($roleTemplate, User::ROLE_ADMIN_LABEL, $glyph->bootstrapGlyph('briefcase'));
            }
            if (in_array(User::ROLE_ADMIN_USER, $entityRoles)) {
                $roles .= sprintf($roleTemplate, User::ROLE_ADMIN_USER_LABEL, $glyph->bootstrapGlyph('user'));
            }
            if (in_array(User::ROLE_ADMIN_EVENT_GLOBAL, $entityRoles)) {
                $roles .= sprintf($roleTemplate, User::ROLE_ADMIN_EVENT_GLOBAL_LABEL, $glyph->bootstrapGlyph('tags'));
            }
            if (in_array(User::ROLE_ADMIN_EVENT, $entityRoles)) {
                $roles .= sprintf($roleTemplate, User::ROLE_ADMIN_EVENT_LABEL, $glyph->bootstrapGlyph('tag'));
            }
            if (in_array(User::ROLE_ADMIN_NEWSLETTER, $entityRoles)) {
                $roles .= sprintf($roleTemplate, User::ROLE_ADMIN_NEWSLETTER_LABEL, $glyph->bootstrapGlyph('envelope'));
            }
            if (in_array(User::ROLE_EMPLOYEE, $entityRoles)) {
                $roles .= sprintf($roleTemplate, User::ROLE_EMPLOYEE_LABEL, $glyph->bootstrapGlyph('heart'));
            }
            if (in_array(User::ROLE_CLOUD, $entityRoles)) {
                $roles .= sprintf($roleTemplate, User::ROLE_CLOUD_LABEL, $glyph->bootstrapGlyph('cloud'));
            }

            $userList[] = [
                'uid'       => $entity->getUid(),
                'email'     => $entity->getEmail(),
                'nameFirst' => $entity->getNameFirst(),
                'nameLast'  => $entity->getNameLast(),
                'roles'     => $roles,
            ];
        }

        return new JsonResponse($userList);
    }

    /**
     * @ParamConverter("user", class="AppBundle:User", options={"id" = "uid"})
     * @Route("/admin/user/{uid}", requirements={"uid": "\d+"}, name="user_detail")
     * @Security("is_granted('ROLE_ADMIN_USER')")
     */
    public function userDetailAction(Request $request, User $user)
    {
        $form = $this->createForm(
            UserRoleAssignmentType::class,
            [
                'uid'  => $user->getUid(),
                'role' => $user->getRoles(),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();

            $roleList = $form->get('role')->getData();
            $user->setRoles($roleList);
            $em->persist($user);
            $em->flush();
        }

        return $this->render(
            'user/detail.html.twig',
            [
                'user'       => $user,
                'userIsSelf' => ($user->getUid() == $this->getUser()->getUid()),
                'form'       => $form->createView(),
            ]
        );
    }


    /**
     * Data provider for events participants list grid
     *
     * @ParamConverter("user", class="AppBundle:User", options={"id" = "uid"})
     * @Route("/admin/user/{uid}/participations.json", requirements={"uid": "\d+"},
     *                                                 name="admin_user_participations_list_data")
     * @Security("is_granted('ROLE_ADMIN_USER')")
     */
    public function listParticipantsDataAction(Request $request, User $user)
    {
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        $participationList       = $participationRepository->findBy(
            ['assignedUser' => $user->getUid(), 'deletedAt' => null]
        );

        $participationListResult = [];
        /** @var Participant $participant */
        foreach ($participationList as $participation) {
            /** @var Event $event */
            $event = $participation->getEvent();

            $participants = [];
            foreach ($participation->getParticipants() as $participant) {
                $participants[] = $participant->getNameFirst();
            }

            $participationListResult[] = [
                'eid'          => $event->getEid(),
                'pid'          => $participation->getPid(),
                'eventTitle'   => $event->getTitle(),
                'participants' => implode(', ', $participants),
            ];
        }

        return new JsonResponse($participationListResult);
    }

}
