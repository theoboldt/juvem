<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Comment repository
 */
class CommentRepository extends EntityRepository
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
	 * Creates comment query builder, adds audit fields, applies order
	 *
	 * @param string $alias
	 * @return QueryBuilder
	 */
	protected function createCommentQueryBuilder($alias)
	{
		$qb = $this->getEntityManager()->createQueryBuilder()
		           ->select('c', 'r')#, 'uc', 'um')
		           ->from($alias, 'c')
#		           ->leftJoin('c.createdBy', 'uc')
#		           ->leftJoin('c.modifiedBy', 'um')
		           ->orderBy('c.createdAt', 'ASC');
		return $qb;
	}

}
