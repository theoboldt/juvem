<?php

namespace AppBundle\Manager;

use AppBundle\Entity\CommentRepository;
use AppBundle\Entity\Participant;
use AppBundle\Entity\ParticipantComment;
use AppBundle\Entity\Participation;
use AppBundle\Entity\ParticipationComment;
use AppBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

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
	 * @var CommentRepository
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
	protected $currentUser = null;

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

		$this->repository = $this->doctrine->getRepository('AppBundle:ParticipantComment');
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
		if (!isset($this->cache[$pid]) || !isset($this->cache[$pid]['comments'])) {
			$this->cache[$pid]['comments'] = $this->repository->findForParticipation($participation);
		}
		return $this->cache[$pid]['comments'];
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

		if (!isset($this->cache[$pid]) || !isset($this->cache[$pid]['participants'])) {
			$this->cache[$pid]['participants'] = $this->repository->findForParticipantsOfParticipation($participation);
		}
		if (isset($this->cache[$pid]['participants'][$aid])) {
			return $this->cache[$pid]['participants'][$aid];
		} else {
			return [];
		}
	}
}