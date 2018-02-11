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


use AppBundle\Entity\Event;
use AppBundle\Entity\ParticipantPaymentEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Participant;
use AppBundle\InvalidTokenHttpException;
use AppBundle\Manager\PaymentManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminPaymentController extends Controller
{

    const ACTION_PAYMENT_RECEIVED = 'paymentReceived';
    const ACTION_PRICE_SET        = 'newPrice';


    /**
     * Handle payment or price change
     *
     * @Route("/admin/event/participant/price", name="admin_participation_price")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function participantPaymentAction(Request $request)
    {
        $token       = $request->get('_token');
        $action      = $request->get('action');
        $aids        = explode(';', $request->get('aids'));
        $value       = $request->get('value');
        $description = $request->get('description');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('Participationprice')) {
            throw new InvalidTokenHttpException();
        }
        $participants = $this->extractParticipantsFromAid($aids, 'participants_edit');

        /** @var PaymentManager $paymentManager */
        $paymentManager = $this->get('app.payment_manager');
        $price          = PaymentManager::convertEuroToCent($value);

        switch ($action) {
            case self::ACTION_PRICE_SET:
                $paymentManager->setPrice($participants, $price, $description);
                break;
            case self::ACTION_PAYMENT_RECEIVED:
                $price = $price * -1; //flip the sign of value

                if (count($participants) === 1) {
                    $participant = reset($participants);
                    $paymentManager->paymentForParticipant($participant, $price, $description);
                } else {
                    throw new BadRequestHttpException();
                }
                break;
            default:
                throw new BadRequestHttpException('Unknown action "' . $action . '" transmitted');
        }

        return new JsonResponse(
            [
                'success'         => true,
                'payment_history' => $this->paymentHistory($participants),
                'to_pay'          => $this->toPay($participants),
            ]
        );
    }

    /**
     *
     * @Route("/admin/event/participant/price/history", methods={"POST"}, name="admin_participation_price_history")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @param Request $request
     * @return JsonResponse
     */
    public function participantsPaymentHistory(Request $request)
    {
        $aids         = explode(';', $request->get('aids'));
        $participants = $this->extractParticipantsFromAid($aids, 'participants_read');


        return new JsonResponse(
            [
                'payment_history' => $this->paymentHistory($participants),
                'to_pay'          => $this->toPay($participants),
            ]
        );
    }

    /**
     * Get flat array list of payment events
     *
     * @param array $participants List of participants
     * @return array
     */
    private function paymentHistory(array $participants)
    {
        /** @var PaymentManager $paymentManager */
        $paymentManager = $this->get('app.payment_manager');
        $paymentEvents  = $paymentManager->paymentHistoryForParticipantList($participants);
        $flatEvents     = [];

        /** @var ParticipantPaymentEvent $paymentEvent */
        foreach ($paymentEvents as $paymentEvent) {
            $user         = $paymentEvent->getCreatedBy();
            $participant  = $paymentEvent->getParticipant();
            $flatEvents[] = [
                'created_by_name'  => $user->userFullname(),
                'created_by_uid'   => $user->getUid(),
                'created_at'       => $paymentEvent->getCreatedAt()->format(Event::DATE_FORMAT_DATE_TIME),
                'participant_name' => Participant::fullname($participant->getNameLast(), $participant->getNameFirst()),
                'participant_aid'  => Participant::fullname($participant->getAid()),
                'value'            => number_format($paymentEvent->getValue(true), 2, ',', '.'),
                'description'      => $paymentEvent->getDescription(),
                'type'             => $paymentEvent->getEventType(),
                'type_label'       => $paymentEvent->getEventTypeLabeled(),
            ];
        }
        return $flatEvents;
    }

    /**
     * Get value which still needs to be payed for transmitted list of participants
     *
     * @param array $participants List of @see Participant
     * @return array
     */
    private function toPay(array $participants)
    {
        /** @var PaymentManager $paymentManager */
        $paymentManager = $this->get('app.payment_manager');
        /** @var Participant $participant */

        $detailedValues = [];

        foreach ($participants as $participant) {
            $value            = $paymentManager->toPayValueForParticipant($participant, true);
            $detailedValues[] = [
                'participant_name' => Participant::fullname($participant->getNameLast(), $participant->getNameFirst()),
                'participant_aid'  => Participant::fullname($participant->getAid()),
                'value'            => number_format($value, 2, ',', '.'),
            ];
        }

        return $detailedValues;
    }

    /**
     * Extract @see Participant entities from aid list
     *
     * @param array  $aidList            List of aids
     * @param string $requiredPermission Permission required for this operation
     * @return Participant[]
     */
    private function extractParticipantsFromAid(array $aidList, string $requiredPermission)
    {
        $participants = [];
        $repository   = $this->getDoctrine()->getRepository('AppBundle:Participant');
        foreach ($aidList as $aid) {
            /** @var Participant $participant */
            $participant = $repository->findOneBy(['aid' => $aid]);
            if (!$participant) {
                throw new NotFoundHttpException('Participant not found');
            }
            $this->denyAccessUnlessGranted($requiredPermission, $participant->getEvent());
            $participants[] = $participant;
        }
        return $participants;
    }
}