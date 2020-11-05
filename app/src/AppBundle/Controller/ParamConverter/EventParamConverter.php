<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\ParamConverter;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\EventNotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class EventParamConverter implements ParamConverterInterface
{
    /**
     * EventRepository
     *
     * @var EventRepository
     */
    private $repository;

    /**
     * EventParamConverter constructor.
     *
     * @param EventRepository $repository
     */
    public function __construct(EventRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException              When unable to guess how to get a Doctrine instance from the request
     *                                      information
     * @throws EventNotFoundHttpException   When object not found
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $name    = $configuration->getName();
        $options = $configuration->getOptions();

        if (!isset($options['id'])) {
            throw new \InvalidArgumentException('ID parameter must be specified');
        }
        $event = null;
        $eid   = (int)$request->get($options['id']);

        if (!$eid) {
            throw new EventNotFoundHttpException('No eid transmitted');
        }

        $include = isset($options['include']) ? $options['include'] : null;
        $event   = $this->find($eid, $include);

        if (!$event) {
            throw new EventNotFoundHttpException();
        }

        $request->attributes->set($name, $event);

        return true;
    }

    /**
     * Do event entity fetch
     *
     * @param int         $eid     Desired Event id
     * @param string|null $include String defining which event related data should be fetched as well
     * @return Event|null Desired event
     */
    private function find(int $eid, string $include = null)
    {
        switch ($include) {
            case 'acquisition_attributes':
                return $this->repository->findWithAcquisitionAttributes($eid);
            case 'participations':
                return $this->repository->findWithParticipations($eid);
            case 'participants':
                return $this->repository->findWithParticipants($eid);
                break;
            case 'users':
                return $this->repository->findWithUserAssignments($eid);
                break;
            default:
                return $this->repository->find($eid);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration)
    {
        $class = $configuration->getClass();
        return ($class == 'AppBundle:Event' || $class === Event::class);
    }
}