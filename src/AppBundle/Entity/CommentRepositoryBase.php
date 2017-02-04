<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

abstract class CommentRepositoryBase extends EntityRepository
{
	/**
	 * Fetch list of all @see CommentBase for transmitted @see Participation
	 *
	 * @param Participation $participation Related participation
	 * @return ParticipationComment[]
	 */
	public function findForParticipation(Participation $participation)
	{
		$qb = $this->createCommentQueryBuilder('AppBundle:ParticipationComment');

		$qb->innerJoin('c.participation', 'r')
           ->andWhere('c.participation = :r');
        $qb->setParameter('r', $participation);

		return $qb->getQuery()->execute();
	}

	/**
	 * Fetch list of all @see CommentBase for transmitted @see Participant
	 *
	 * @param Participant $participant Related participant
	 * @return ParticipantComment[]
	 */
	public function findForParticipant(Participant $participant)
	{
		$qb = $this->createCommentQueryBuilder('AppBundle:ParticipantComment');

		$qb->innerJoin('c.participant', 'r')
           ->andWhere('c.participant = :r');
        $qb->setParameter('r', $participant);

		return $qb->getQuery()->execute();
	}

	/**
	 * Fetch list of all @see CommentBase for all Participations of transmitted participation
	 *
	 * @param Participation $participation Related participation
	 * @param bool          $returnFlat    Set to true to return flat list of @see ParticipantComment entities, set to
	 *                                     false to get a list of comments identified by the participant
	 * @return ParticipantComment[]|array
	 */
	public function findForParticipantsOfParticipation(Participation $participation, $returnFlat = false)
	{
		$qb = $this->createCommentQueryBuilder('AppBundle:ParticipantComment');
		$qb->innerJoin('c.participant', 'r')
		   ->andWhere(
			   'c.participant IN (SELECT x.aid FROM AppBundle:Participant x WHERE x.participation = :participation)'
		   );
		$qb->setParameter('participation', $participation);

		$flatResult = $qb->getQuery()->execute();
		if ($returnFlat) {
			return $flatResult;
		} else {
			$result = [];
			/** @var ParticipantComment $comment */
			foreach ($flatResult as $comment) {
				$aid = $comment->getParticipant()->getAid();
				if (!isset($result[$aid])) {
					$result[$aid] = [];
				}
				$result[$aid][] = $comment;
			}
	        return $result;
        }
	}


	/**
	 * Creates comment query builder, adds audit fields, applies order
	 *
	 * @param string $alias
	 * @return QueryBuilder
	 */
	protected function createCommentQueryBuilder($alias)
	{
		$qb = $this->getEntityManager()->createQueryBuilder()
		           ->select('c', 'r', 'uc', 'um')
		           ->from($alias, 'c')
		           ->leftJoin('c.createdBy', 'uc')
		           ->leftJoin('c.modifiedBy', 'um')
                   ->andWhere('c.deletedAt IS NULL')
		           ->orderBy('c.createdAt', 'DESC');
		return $qb;
	}

}
