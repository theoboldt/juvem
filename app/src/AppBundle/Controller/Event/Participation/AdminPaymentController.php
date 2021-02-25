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


use AppBundle\Controller\AuthorizationAwareControllerTrait;
use AppBundle\Controller\DoctrineAwareControllerTrait;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\ParticipantPaymentEvent;
use AppBundle\Entity\Participation;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\InvalidTokenHttpException;
use AppBundle\Manager\Invoice\InvoiceManager;
use AppBundle\Manager\Payment\PaymentManager;
use AppBundle\Manager\Payment\PriceSummand\AttributeAwareInterface;
use AppBundle\Manager\Payment\PriceSummand\SummandInterface;
use AppBundle\SerializeJsonResponse;
use AppBundle\Twig\Extension\PaymentInformation;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminPaymentController
{
    use DoctrineAwareControllerTrait, AuthorizationAwareControllerTrait;
    
    const ACTION_PAYMENT_RECEIVED = 'paymentReceived';
    const ACTION_PRICE_SET        = 'newPrice';
    
    /**
     * security.csrf.token_manager
     *
     * @var CsrfTokenManagerInterface
     */
    private CsrfTokenManagerInterface $csrfTokenManager;
    
    /**
     * app.payment.invoice_manager
     *
     * @var InvoiceManager
     */
    private InvoiceManager $invoiceManager;
    
    /**
     * app.payment_manager
     *
     * @var PaymentManager
     */
    private PaymentManager $paymentManager;
    
    /**
     * AdminPaymentController constructor.
     *
     * @param ManagerRegistry $doctrine
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param InvoiceManager $invoiceManager
     * @param PaymentManager $paymentManager
     */
    public function __construct(
        ManagerRegistry $doctrine,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        CsrfTokenManagerInterface $csrfTokenManager,
        InvoiceManager $invoiceManager,
        PaymentManager $paymentManager
    )
    {
        $this->csrfTokenManager     = $csrfTokenManager;
        $this->invoiceManager       = $invoiceManager;
        $this->paymentManager       = $paymentManager;
        $this->doctrine             = $doctrine;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage         = $tokenStorage;
    }
    
    
    /**
     * Handle payment or price change
     *
     * @CloseSessionEarly
     * @Route("/admin/event/participant/price", methods={"POST"}, name="admin_participation_price")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     */
    public function participantPaymentAction(Request $request)
    {
        $token       = $request->get('_token');
        $action      = $request->get('action');
        $aids        = explode(';', $request->get('aids'));
        $value       = $request->get('value');
        $description = $request->get('description');
        
        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->csrfTokenManager;
        if ($token != $csrf->getToken('Participationprice')) {
            throw new InvalidTokenHttpException();
        }
        $participants = $this->extractParticipantsFromAid($aids, 'participants_edit');
        
        /** @var PaymentManager $paymentManager */
        $paymentManager = $this->paymentManager;
        $price          = PaymentManager::convertEuroToCent($value);
        
        switch ($action) {
            case self::ACTION_PRICE_SET:
                $paymentManager->setBasePrice($participants, $price, $description);
                break;
            case self::ACTION_PAYMENT_RECEIVED:
                $price = $price * -1; //flip the sign of value
                
                if (count($participants) === 1) {
                    $participant = reset($participants);
                    $paymentManager->handlePaymentForParticipant($participant, $price, $description);
                } else {
                    $paymentManager->handlePaymentForParticipants($participants, $price, $description);
                }
                break;
            default:
                throw new BadRequestHttpException('Unknown action "' . $action . '" transmitted');
        }
        return new SerializeJsonResponse(
            array_merge(['success' => true], $this->getPaymentResponseData($participants))
        );
    }
    
    
    /**
     *
     * @CloseSessionEarly
     * @Route("/admin/event/participant/price/history", name="admin_participation_price_history")
     * @Security("is_granted('ROLE_ADMIN_EVENT')")
     * @param Request $request
     * @return JsonResponse
     */
    public function participantsPaymentHistory(Request $request)
    {
        $aids         = explode(';', $request->get('aids'));
        $participants = $this->extractParticipantsFromAid($aids, 'participants_read');
        
        return new SerializeJsonResponse($this->getPaymentResponseData($participants));
    }
    
    /**
     * Generate payment info data for transmitted participants
     *
     * @param array|Participant[] $participants List of @see Participant
     * @return array
     */
    private function getPaymentResponseData(array $participants): array
    {
        $toPayList      = $this->getParticipantsToPayList($participants);
        $toPayTotalCent = null;
        foreach ($toPayList as $payItem) {
            $payValue = $payItem['to_pay_value_cent'];
            if (is_numeric($payValue)) {
                $toPayTotalCent += $payValue;
            }
        }
        $priceTags    = $this->getParticipantsPriceTagList($participants);
        $priceSumCent = 0;
        foreach ($priceTags as $summand) {
            $priceSumCent += $summand['value_cent'];
        }
        /** @var InvoiceManager $invoiceManager */
        $invoiceManager = $this->invoiceManager;
        $participant    = reset($participants);
        
        return [
            'payment_history' => $this->paymentHistory($participants),
            'price_tag_list'  => $priceTags,
            'price_tag_sum'   => $priceSumCent/100,
            'to_pay_list'     => $toPayList,
            'to_pay_sum'      => $toPayTotalCent/100,
            'to_pay_euro'     => $toPayTotalCent/100,
            'invoice_list'    => $invoiceManager->getInvoicesForParticipation($participant->getParticipation())
        ];
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
        $paymentManager = $this->paymentManager;
        $paymentEvents  = $paymentManager->getPaymentHistoryForParticipantList($participants);
        $flatEvents     = [];
        
        /** @var ParticipantPaymentEvent $paymentEvent */
        foreach ($paymentEvents as $paymentEvent) {
            $user         = $paymentEvent->getCreatedBy();
            $participant  = $paymentEvent->getParticipant();
            $flatEvents[] = [
                'created_by_name'  => $user === null ? 'System' : $user->userFullname(),
                'created_by_uid'   => $user === null ? null : $user->getUid(),
                'created_at'       => $paymentEvent->getCreatedAt()->format(Event::DATE_FORMAT_DATE_TIME),
                'participant_name' => $participant->fullname(),
                'participant_aid'  => $participant->getAid(),
                'value'            => $paymentEvent->getValue(true),
                'description'      => $paymentEvent->getDescription(),
                'type'             => $paymentEvent->getEventType(),
                'type_label'       => $paymentEvent->getEventTypeLabeled(),
            ];
        }
        return $flatEvents;
    }
    
    /**
     * Get price tags for data
     *
     * @param array $participants
     * @return array
     */
    private function getParticipantsPriceTagList(array $participants)
    {
        /** @var PaymentManager $paymentManager */
        $paymentManager = $this->paymentManager;
        /** @var Participant $participant */
        
        $result = [];
        
        foreach ($participants as $participant) {
            if ($participant->isWithdrawn() || $participant->isRejected() || $participant->getDeletedAt()) {
                continue; //do not take into account
            }
    
            $priceTag = $paymentManager->getEntityPriceTag($participant);
    
            /** @var SummandInterface $summand */
            foreach ($priceTag->getSummands() as $summand) {
                $attributeName = ($summand instanceof AttributeAwareInterface)
                    ? $summand->getAttribute()->getManagementTitle() : null;
        
                $valueEuro = $summand->getValue(true);
                $valueCent = $summand->getValue(false);
        
                $result[] = [
                    'participant_name'         => $participant->fullname(),
                    'participant_aid'          => $participant->getId(),
                    'is_participation_summand' => ($summand->getCause() instanceof Participation),
                    'value'                    => $valueEuro,
                    'value_euro'               => $valueEuro,
                    'value_cent'               => $valueCent,
                    'type'                     => get_class($summand),
                    'attribute_name'           => $attributeName,
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Get value which still needs to be payed for transmitted list of participants
     *
     * @param array $participants List of @see Participant
     * @return array
     */
    private function getParticipantsToPayList(array $participants)
    {
        /** @var PaymentManager $paymentManager */
        $paymentManager = $this->paymentManager;
        /** @var Participant $participant */
        
        $detailedValues = [];
        
        foreach ($participants as $participant) {
            if ($participant->isWithdrawn() || $participant->isRejected() || $participant->getDeletedAt()) {
                continue; //do not take into account
            }
    
            $toPayValueEuro   = $paymentManager->getToPayValueForParticipant($participant, true);
            $toPayValueCents  = $paymentManager->getToPayValueForParticipant($participant, false);
            $priceValueEuro   = $paymentManager->getPriceForParticipant($participant, true);
            $priceValueCents  = $paymentManager->getPriceForParticipant($participant, false);
            $detailedValues[] = [
                'participant_name'  => $participant->fullname(),
                'participant_aid'   => $participant->getAid(),
                'to_pay_value'      => $toPayValueEuro,
                'to_pay_value_euro' => $toPayValueEuro,
                'to_pay_value_cent' => $toPayValueCents,
                'price_html'        => PaymentInformation::provideInfo(
                    $paymentManager->getParticipantPaymentStatus($participant)
                ),
                'price_value'       => $priceValueEuro,
                'price_value_euro'  => $priceValueEuro,
                'price_value_cent'  => $priceValueCents,
            ];
        }
        
        return $detailedValues;
    }
    
    /**
     * Extract @see Participant entities from aid list
     *
     * @param array $aidList             List of aids
     * @param string $requiredPermission Permission required for this operation
     * @return Participant[]
     */
    private function extractParticipantsFromAid(array $aidList, string $requiredPermission)
    {
        $participants = [];
        $repository   = $this->getDoctrine()->getRepository(Participant::class);
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
