<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;

use AppBundle\Twig\Extension\BootstrapGlyph;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends Controller
{
    /**
     * @Route("/admin/user/list", name="user_list")
     */
    public function listAction(Request $request)
    {
        return $this->render('user/list.html.twig');
    }

    /**
     * Data provider for event list grid
     *
     * @Route("/admin/user/list.json", name="user_list_data")
     */
    public function listDataAction(Request $request)
    {
        $glyph = new BootstrapGlyph();


        $userManager = $this->container->get('fos_user.user_manager');
        $entityList  = $userManager->findUsers();

        $userList = array();

        /** @var User $entity */
        foreach ($entityList as $entity) {
            $entityRoles = $entity->getRoles();

            $roles = '';
            if (in_array('ROLE_USER', $entityRoles)) {
                $roles .= sprintf(
                    '<span title="%s">%s</span>',
                    'Benutzer',
                    $glyph->bootstrapGlyph('pawn')
                );
            }
            if (in_array('ROLE_SUPER_ADMIN', $entityRoles)) {
                $roles .= sprintf(
                    '<span title="%s">%s</span>',
                    'Administrator',
                    $glyph->bootstrapGlyph('king')
                );
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
     */
    public function userDetailAction($uid)
    {
        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:User');

        $user = $repository->findOneBy(array('id' => $uid));

        return $this->render('user/detail.html.twig', array('user' => $user));
    }

    /**
     * Create a new user
     *
     * @Route("/admin/user/new", name="user_new")
     */
    public function newAction(Request $request)
    {
        throw new \InvalidArgumentException('Not yet implemented');
    }

}