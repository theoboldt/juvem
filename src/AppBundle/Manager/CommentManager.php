<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager;

use AppBundle\Entity\CommentBase;
use AppBundle\Entity\CommentRepositoryBase;
use AppBundle\Entity\Employee;
use AppBundle\Entity\EmployeeComment;
use AppBundle\Entity\Participant;
use AppBundle\Entity\ParticipantComment;
use AppBundle\Entity\Participation;
use AppBundle\Entity\ParticipationComment;
use AppBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class CommentManager
 *
 * @package AppBundle\Manager
 */
class CommentManager
{
	/**
	 * Database abstraction
	 *
	 * @var Registry
	 */
	protected $doctrine;

	/**
	 * Comment repository
	 *
	 * @var CommentRepositoryBase
	 */
	protected $repository;

	/**
	 * @var array
	 */
	protected $cache;

	/**
	 * The user currently logged in
	 *
	 * @var User|null
	 */
	protected $user = null;

	/**
	 * CommentManager constructor.
	 *
	 * @param Registry     $doctrine
	 * @param TokenStorage $tokenStorage
	 */
	public function __construct(Registry $doctrine, TokenStorage $tokenStorage = null)
	{
		$this->doctrine = $doctrine;
		if ($tokenStorage) {
			$this->user = $tokenStorage->getToken()->getUser();
		}

		$this->repository = $this->doctrine->getRepository(ParticipantComment::class);
	}

    /**
     * Creates a comment, invalidates related caches
     *
     * @param   string  $property  Related comment class
     * @param   integer $relatedId Related entity's id
     * @param   string  $content   Comment content
     * @return  CommentBase        Resulted comment object
     */
    public function createComment($property, $relatedId, $content)
    {
        switch ($property) {
            case ParticipationComment::class:
                $relatedEntity = $this->doctrine->getRepository(Participation::class)->findOneBy(
                    ['pid' => $relatedId]
                );
                if (!$relatedEntity) {
                    throw new \InvalidArgumentException('Related participation was not found');
                }
                $comment = new ParticipationComment();
                $comment->setParticipation($relatedEntity);
                break;
            case ParticipantComment::class:
                $relatedEntity = $this->doctrine->getRepository(Participant::class)->findOneBy(
                    ['aid' => $relatedId]
                );
                if (!$relatedEntity) {
                    throw new \InvalidArgumentException('Related participant was not found');
                }
                $comment = new ParticipantComment();
                $comment->setParticipant($relatedEntity);
                break;
            default:
                throw new \InvalidArgumentException('Unknown property class transmitted');
                break;
        }
        $comment->setCreatedBy($this->user);
        $comment->setContent($content);

        $em = $this->doctrine->getManager();
        $em->persist($comment);
        $em->flush();

        $this->invalidateRelatedCache($comment);

        return $comment;
    }

    /**
     * Update an existing comment, invalidates caches
     *
     * @param CommentBase $comment  Comment entity to update
     * @param string      $content  New content
     * @return CommentBase          Updated comment
     */
    public function updateComment(CommentBase $comment, $content)
    {
        if ($comment->getCreatedBy() != $this->user) {
            throw new \InvalidArgumentException('You are not allowed to update comments of other users');
        }
        $comment->setModifiedBy($this->user);
        $comment->setContent($content);

        $em = $this->doctrine->getManager();
        $em->persist($comment);
        $em->flush();

        $this->invalidateRelatedCache($comment);

        return $comment;
    }

    /**
     * Delete an existing comment, invalidates caches
     *
     * @param CommentBase $comment  Comment entity to delete
     */
    public function deleteComment(CommentBase $comment)
    {
        if ($comment->getCreatedBy() != $this->user) {
            throw new \InvalidArgumentException('You are not allowed to update comments of other users');
        }
        $comment->setDeletedAt(new \DateTime());

        $em = $this->doctrine->getManager();
        $em->persist($comment);
        $em->flush();

        $this->invalidateRelatedCache($comment);
    }
    /**
     * Fetch a comment by id and related class name
     *
     * @param   integer $cid        Desired comment id
     * @param   string  $property   Related comment class
     * @return null|object
     */
	public function findByCidAndType($cid, $property) {
	    if (!self::isCommentClassValid($property)){
	        throw new \InvalidArgumentException('Invalid comment class transmitted');
        }
        return $this->doctrine->getRepository($property)->findOneBy(['cid' => $cid]);
    }
    
    /**
     * Fetch amount of comments for transmitted @see Employee
     *
     * @param Employee $employee Related employee
     * @return integer
     */
    public function countForEmployee(Employee $employee)
    {
        return count($this->forEmployee($employee));
    }
    
    /**
     * Fetch list of all @see CommentBase for transmitted @see Employee
     *
     * @param Employee $employee Related employee
     * @return EmployeeComment[]
     */
    public function forEmployee(Employee $employee)
    {
        $pid = $employee->getGid();
        if (!isset($this->cache['employee'][$pid]) || !isset($this->cache['employee'][$pid]['comments'])) {
            $this->cache['employee'][$pid]['comments'] = $this->repository->findForEmployee($employee);
        }
        return $this->cache['employee'][$pid]['comments'];
    }
	
	/**
	 * Fetch amount of comments for transmitted @see Participation
	 *
	 * @param Participation $participation Related participation
	 * @return integer
	 */
	public function countForParticipation(Participation $participation)
	{
	    return count($this->forParticipation($participation));
    }

	/**
	 * Fetch list of all @see CommentBase for transmitted @see Participation
	 *
	 * @param Participation $participation Related participation
	 * @return ParticipationComment[]
	 */
	public function forParticipation(Participation $participation)
	{
		$pid = $participation->getPid();
		if (!isset($this->cache['participation'][$pid]) || !isset($this->cache['participation'][$pid]['comments'])) {
			$this->cache['participation'][$pid]['comments'] = $this->repository->findForParticipation($participation);
		}
		return $this->cache['participation'][$pid]['comments'];
	}

	/**
	 * Fetch amount of comments for transmitted @see Participant
	 *
	 * @param Participant $participant Related participant
	 * @return integer
	 */
	public function countForParticipant(Participant $participant)
	{
        return count($this->forParticipant($participant));
    }

	/**
	 * Fetch list of all @see CommentBase for transmitted @see Participant
	 *
	 * @param Participant $participant Related participant
	 * @return ParticipantComment[]
	 */
	public function forParticipant(Participant $participant)
	{
		$participation = $participant->getParticipation();
		$pid           = $participation->getPid();
		$aid           = $participant->getAid();

		if (!isset($this->cache['participation'][$pid]) || !isset($this->cache['participation'][$pid]['participants'])) {
			$this->cache['participation'][$pid]['participants'] = $this->repository->findForParticipantsOfParticipation($participation);
		}
		if (isset($this->cache['participation'][$pid]['participants'][$aid])) {
			return $this->cache['participation'][$pid]['participants'][$aid];
		} else {
			return [];
		}
	}

    /**
     * Verify if transmitted class is in supported comment class list
     *
     * @param   string $class Class name to check
     * @return  bool
     */
    public static function isCommentClassValid($class)
    {
        return in_array($class, [ParticipationComment::class, ParticipantComment::class]);
    }

    /**
     * Invalid caches as required by changes of related comment
     *
     * @param CommentBase $comment  Comment of which related cached entries should be deleted
     * @return  void
     */
    public function invalidateRelatedCache(CommentBase $comment)
    {
        $relatedEntity = $comment->getRelated();
        switch ($comment->getBaseClassName()) {
            case ParticipationComment::class:
                /** @var Participation $relatedEntity */
                $this->invalidCache($relatedEntity->getPid());
                break;
            case ParticipantComment::class:
                /** @var Participant $relatedEntity */
                $this->invalidCache($relatedEntity->getParticipation()->getPid(), $relatedEntity->getAid());
                break;
            default:
                throw new \InvalidArgumentException('Unknown property class transmitted');
                break;
        }
    }
    
    /**
     * Invalidate caches
     *
     * @param int|null $pid Related pid to clear. If not transmitted, complete cache is deleted
     * @param int|null $aid Related aid to clear. If not transmitted, comments of related $pid deleted
     * @param int|null $gid Related gid to clear. If not transmitted
     */
    public function invalidCache($pid = null, $aid = null, int $gid = null)
    {
        if ($gid && isset($this->cache['employee'][$gid])) {
            unset($this->cache['employee'][$gid]);
            return;
        }
        if ($pid) {
            if ($aid) {
                if (isset($this->cache['participation'][$pid]) &&
                    isset($this->cache['participation'][$pid]['participants']) &&
                    isset($this->cache['participation'][$pid]['participants'][$aid])
                ) {
                    unset($this->cache['participation'][$pid]['participants'][$aid]);
                    return;
                }
            } else {
                if (isset($this->cache['participation'][$aid]) &&
                    isset($this->cache['participation'][$aid]['comments'])) {
                    unset($this->cache['participation'][$aid]['comments']);
                    return;
                }
            }
        }
        unset($this->cache);
    }
}