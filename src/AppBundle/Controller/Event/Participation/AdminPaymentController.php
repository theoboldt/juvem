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
use AppBundle\Entity\Invoice;
use AppBundle\Entity\ParticipantPaymentEvent;
use AppBundle\Entity\Participation;
use AppBundle\Manager\Invoice\InvoiceManager;
use AppBundle\Manager\Payment\PriceSummand\AttributeAwareInterface;
use AppBundle\Manager\Payment\PriceSummand\SummandInterface;
use AppBundle\SerializeJsonResponse;
use AppBundle\Twig\Extension\PaymentInformation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Participant;
use AppBundle\InvalidTokenHttpException;
use AppBundle\Manager\Payment\PaymentManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
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
     * @Route("/admin/event/participant/price", methods={"POST"}, name="admin_participation_price")
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
     * Create invoice for selected @see Participation
     *
     * @Route("/admin/event/participation/invoice/create", methods={"POST"}, name="admin_invoice_create")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     * @param Request $request
     * @return JsonResponse
     */
    public function createInvoiceAction(Request $request)
    {
        $token = $request->get('_token');
        $pid   = $request->get('pid');
        
        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('ParticipationcreateInvoice' . $pid)) {
            throw new InvalidTokenHttpException();
        }
        
        $participationRepository = $this->getDoctrine()->getRepository(Participation::class);
        
        $participation = $participationRepository->findDetailed($request->get('pid'));
        if (!$participation) {
            throw new BadRequestHttpException('Requested participation event not found');
        }
        $event = $participation->getEvent();
        $this->denyAccessUnlessGranted('participants_read', $event);
        
        $invoiceManager = $this->get('app.payment.invoice_manager');
        $invoice        = $invoiceManager->createInvoice($participation);
        
        return new SerializeJsonResponse(
            [
                'success'      => true,
                'invoice'      => $invoice,
                'invoice_list' => $invoiceManager->getInvoicesForParticipation($participation)
            ]
        );
    }
    
    /**
     * Download created invoice
     *
     * @Route("/admin/event/{eid}/participation/invoice/{id}/{filename}", requirements={"eid": "\d+","id": "\d+", "filename": "([a-zA-Z0-9\s_\\.\-\(\):])+"}, name="admin_invoice_download")
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @ParamConverter("invoice", class="AppBundle:Invoice", options={"id" = "id"})
     * @Security("is_granted('participants_read', event)")
     * @return BinaryFileResponse
     */
    public function downloadInvoiceAction(Event $event, Invoice $invoice, string $filename)
    {
        if ($invoice->getParticipation()->getEvent()->getEid() !== $event->getEid()) {
            throw new BadRequestHttpException('Incorrect invoice requested');
        }
        $invoiceManager = $this->get('app.payment.invoice_manager');
        
        if (!$invoiceManager->hasFile($invoice)) {
            throw new NotFoundHttpException('There is no file for transmitted invoice stored');
        }
        
        $response = new BinaryFileResponse($invoiceManager->getInvoiceFilePath($invoice));
        $response->headers->set(
            'Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
        );
        
        return $response;
    }
    
    /**
     *
     * @Route("/admin/event/participant/price/history", name="admin_participation_price_history")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
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
        $toPayList  = $this->getParticipantsToPayList($participants);
        $toPayTotal = null;
        foreach ($toPayList as $payItem) {
            $payValue = $payItem['to_pay_value'];
            if (is_numeric($payValue)) {
                $toPayTotal += $payValue;
            }
        }
        $priceTags = $this->getParticipantsPriceTagList($participants);
        $priceSum  = 0;
        foreach ($priceTags as $summand) {
            $priceSum += $summand['value'];
        }
        /** @var InvoiceManager $invoiceManager */
        $invoiceManager = $this->get('app.payment.invoice_manager');
        $participant    = reset($participants);
        
        return [
            'payment_history' => $this->paymentHistory($participants),
            'price_tag_list'  => $priceTags,
            'price_tag_sum'   => $priceSum,
            'to_pay_list'     => $toPayList,
            'to_pay_sum'      => $toPayTotal,
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
        $paymentManager = $this->get('app.payment_manager');
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
        $paymentManager = $this->get('app.payment_manager');
        /** @var Participant $participant */
        
        $result = [];
        
        foreach ($participants as $participant) {
            $priceTag = $paymentManager->getEntityPriceTag($participant);
            
            /** @var SummandInterface $summand */
            foreach ($priceTag->getSummands() as $summand) {
                $attributeName = ($summand instanceof AttributeAwareInterface)
                    ? $summand->getAttribute()->getManagementTitle() : null;
                
                $result[] = [
                    'participant_name'         => $participant->fullname(),
                    'participant_aid'          => $participant->getId(),
                    'is_participation_summand' => ($summand->getCause() instanceof Participation),
                    'value'                    => $summand->getValue(true),
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
        $paymentManager = $this->get('app.payment_manager');
        /** @var Participant $participant */
        
        $detailedValues = [];
        
        foreach ($participants as $participant) {
            $toPayValue       = $paymentManager->getToPayValueForParticipant($participant, true);
            $priceValue       = $paymentManager->getPriceForParticipant($participant, true);
            $detailedValues[] = [
                'participant_name' => $participant->fullname(),
                'participant_aid'  => $participant->getAid(),
                'to_pay_value'     => $toPayValue,
                'price_html'       => PaymentInformation::provideInfo(
                    $paymentManager->getParticipantPaymentStatus($participant)
                ),
                'price_value'      => $priceValue,
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
