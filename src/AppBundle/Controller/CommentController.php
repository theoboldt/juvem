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

use AppBundle\Entity\CommentBase;
use AppBundle\Entity\EmployeeComment;
use AppBundle\Entity\ParticipantComment;
use AppBundle\Entity\ParticipationComment;
use AppBundle\InvalidTokenHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CommentController extends Controller
{

    /**
     * Handler for add/edit comment action
     *
     * @Route("/admin/comment/update", name="admin_comment_update")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @param Request $request
     * @return JsonResponse
     */
    public function addEditCommentAction(Request $request)
    {
        $token      = $request->get('_token');
        $cid        = $request->get('cid');
        $relatedId  = $request->get('relatedId');
        $property   = $request->get('relatedClass');
        $contentNew = $request->get('content');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('Comment')) {
            throw new InvalidTokenHttpException();
        }
        $manager = $this->container->get('app.comment_manager');
        $comment = null;

        if ($cid) {
            /** @var CommentBase $comment */
            $comment = $manager->findByCidAndType($cid, $property);
            if (!$comment) {
                throw new NotFoundHttpException('Comment with related data not found');
            }
            try {
                $manager->updateComment($comment, $contentNew);
            } catch (\InvalidArgumentException $e) {
                throw new AccessDeniedHttpException('You are not allowed to update comments of other users');
            }
        }

        if (!$comment) {
            $comment = $manager->createComment($property, $relatedId, $contentNew);
        }

        $relatedEntity = $comment->getRelated();
        switch ($comment->getBaseClassName()) {
            case ParticipationComment::class:
                $list = $manager->forParticipation($relatedEntity);
                break;
            case ParticipantComment::class:
                $list = $manager->forParticipant($relatedEntity);
                break;
            case EmployeeComment::class:
                $list = $manager->forEmployee($relatedEntity);
                break;
            default:
                throw new \InvalidArgumentException('Unknown property class transmitted');
                break;
        }

        $listHtml = '';
        foreach ($list as $listComment) {
            $listHtml .= $this->container->get('templating')
                                         ->render(
                                             'common/comment-content.html.twig',
                                             ['comment' => $listComment]
                                         );
        }

        return new JsonResponse(['comments' => $listHtml, 'count' => count($list)]);
    }

}