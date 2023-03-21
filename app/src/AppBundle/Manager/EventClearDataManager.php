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

use AppBundle\Entity\Employee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\Participation;
use AppBundle\Entity\User;
use AppBundle\Mail\MailSendService;
use AppBundle\Manager\Invoice\InvoiceManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EventClearDataManager
{


    /**
     * The user currently logged in
     *
     * @var User|null
     */
    protected $user = null;

    /**
     * @var MailSendService
     */
    private MailSendService $mailService;

    /**
     * @var InvoiceManager
     */
    private InvoiceManager $invoiceManager;

    /**
     * EntityManager
     *
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    /**
     * @var KernelInterface
     */
    private KernelInterface $kernel;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * CommentManager constructor.
     *
     * @param InvoiceManager             $invoiceManager
     * @param MailSendService            $mailService
     * @param EntityManagerInterface     $em
     * @param KernelInterface            $kernel
     * @param ParameterBagInterface      $parameterBag
     * @param TokenStorageInterface|null $tokenStorage
     * @param LoggerInterface|null       $logger
     */
    public function __construct(
        InvoiceManager         $invoiceManager,
        MailSendService        $mailService,
        EntityManagerInterface $em,
        KernelInterface        $kernel,
        ParameterBagInterface  $parameterBag,
        ?TokenStorageInterface $tokenStorage = null,
        ?LoggerInterface       $logger = null
    ) {
        $this->invoiceManager = $invoiceManager;
        $this->mailService    = $mailService;
        $this->parameterBag   = $parameterBag;
        $this->kernel         = $kernel;
        $this->em             = $em;
        $this->logger         = $logger;
        if ($tokenStorage) {
            $this->user = $tokenStorage->getToken()->getUser();
        }
    }

    /**
     * Create a
     *
     * @param int $eid
     * @return bool
     */
    private function createDatabaseBackup(int $eid): bool
    {
        $backupPassword        = $this->parameterBag->get('backup_password');
        $backupPath            = rtrim($this->parameterBag->get('app.var.backup.path'), '/');
        $backupArchiveFileName = 'backup_pre_remove_' . $eid . '_' . $this->parameterBag->get('database_name') . '_' .
                                 date('Y-m-d') . '.zip';
        $backupLogFileName     = 'backup_pre_remove_' . $eid . '_' . $this->parameterBag->get('database_name') . '_' .
                                 date('Y-m-d') . '.log';

        if (file_exists($backupPath)) {
            $this->logger->info(
                'Skipping backup creation before clearing event {eid} as file {path} already exists',
                ['eid' => $eid, 'path' => $backupPath]
            );
            return false;
        }
        
        if (!$backupPassword) {
            $this->logger->warning(
                'Can not create database backup before clearing data of event {eid} as no backup password is configured',
                ['eid' => $eid]
            );
            return false;
        }

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $parameters = [
            'command'          => 'app:data:export',
            '--no-interaction' => true,
            'path'             => $backupPath . '/' . $backupArchiveFileName,
            'password'         => $backupPassword,
        ];

        $time   = microtime(true);
        $input  = new ArrayInput($parameters);
        $output = new BufferedOutput();
        $application->run($input, $output);

        $content = $output->fetch();
        file_put_contents($backupPath . '/' . $backupLogFileName, $content);
        $duration = round((microtime(true) - $time) * 1000);

        $this->logger->info(
            'Created database backup before clearing data of event {eid} in {duration} s',
            ['eid' => $eid, 'duration' => $duration]
        );
        return true;
    }

    /**
     * Clear all related data for an event
     *
     * @param Event $event Related event
     * @return string|null Status message
     */
    public function clearEventData(Event $event): ?string
    {
        if ($event->isCleared()) {
            $this->logger->info(
                'Requested data clear for event {eid} but it is already cleared',
                ['eid' => $event->getEid()]
            );
            return null;
        }
        $this->createDatabaseBackup($event->getEid());

        $time = microtime(true);
        $this->logger->debug(
            'Begin clear of data of event {eid}',
            ['eid' => $event->getEid()]
        );
        $this->em->beginTransaction();
        /** @var Invoice[] $invoices */
        $invoices = $this->invoiceManager->getInvoicesForEvent($event);
        foreach ($invoices as $invoice) {
            $this->invoiceManager->removeInvoice($invoice);
        }
        $this->logger->debug(
            'Removed invoices of event {eid}',
            ['eid' => $event->getEid()]
        );

        $qb = $this->em->getConnection()->createQueryBuilder();
        $qb->select('COUNT(*)')
           ->from('employee', 'e')
           ->where($qb->expr()->eq('e.eid', $event->getEid()));
        $employeeCount = (int)$qb->execute()->fetchOne();

        $qb = $this->em->getConnection()->createQueryBuilder();
        $qb->select('COUNT(*)')
           ->from('participant', 'participant')
           ->innerJoin('participant', 'participation', 'participation', 'participation.pid = participant.pid')
           ->andWhere('participation.eid = :eid')
           ->setParameter('eid', $event->getEid());
        $participantsCount = (int)$qb->execute()->fetchOne();

        $qb = $this->em->getConnection()->createQueryBuilder();
        $qb->select('COUNT(*)')
           ->from('participation', 'p')
           ->where($qb->expr()->eq('p.eid', $event->getEid()));
        $participationsCount = (int)$qb->execute()->fetchOne();

        $qb = $this->em->createQueryBuilder();
        $qb->delete(Employee::class, 'em')
           ->where($qb->expr()->eq('em.event', $event->getEid()));
        $qb->getQuery()->execute();

        $qb = $this->em->createQueryBuilder();
        $qb->delete(Participation::class, 'p')
           ->where($qb->expr()->eq('p.event', $event->getEid()));
        $qb->getQuery()->execute();

        $event->setIsCleared(true);
        $event->setIsActive(false);
        $event->setIsVisible(false);
        $this->em->persist($event);
        $this->em->flush();
        $this->em->commit();

        $duration = round((microtime(true) - $time) * 1000);
        $this->logger->notice(
            'Cleared data of event {eid} within {duration} s; Removed {invoices} invoices, {employees} employees, {participants} participants',
            [
                'eid'          => $event->getEid(),
                'duration'     => $duration,
                'invoices'     => count($invoices),
                'employees'    => $employeeCount,
                'participants' => $participantsCount,
            ]
        );

        //notify
        $notify = [];
        if ($this->user) {
            $notify[$this->user->getEmail()] = $this->user->fullname();
        }
        foreach ($event->getUserAssignments() as $userAssignment) {
            $notify[$notify[$this->user->getEmail()]] = $userAssignment->getUser()->fullname();
        }
        $this->logger->notice(
            'Preparing messages for {count} recipients about data clear of event {eid}',
            ['eid' => $event->getEid(), 'count' => count($notify)]
        );

        $messageTitle   = 'Zugehörige Daten der Veranstaltung gelöscht';
        $messageSubject = 'Zugehörige Daten der Veranstaltung ' . $event->getTitle(true) . ' gelöscht';
        $messageContent = 'Die zur Veranstaltung "' . $event->getTitle(true) . '" gehörenden Daten wurden gelöscht.' .
                          "\n\n";

        $messageContent .= 'Dabei wurden ' . count($invoices) . ' Rechnungen, Datensätze von ' . $employeeCount .
                           ' Mitarbeiter:innen und ' . $participantsCount . ' Teilnehmer:innen aus ' .
                           $participationsCount . ' Anmeldungen entfernt.';

        $message = $this->mailService->getTemplatedMessage(
            'general-raw',
            [
                'text' => [
                    'title'   => $messageTitle,
                    'lead'    => $event->getTitle(true),
                    'subject' => $messageSubject,
                    'content' => $messageContent,

                ],
                'html' => [
                    'title'               => $messageTitle,
                    'lead'                => $event->getTitle(true),
                    'subject'             => $messageSubject,
                    'content'             => $messageContent,
                    'calltoactioncontent' => false,
                ],
            ]
        );

        foreach ($notify as $email => $name) {
            $message->setTo($email, $name);

            MailSendService::addRelatedEntityMessageHeader(
                $message, Event::class, $event->getEid()
            );

            $this->mailService->send($message);
        }

        return $messageContent;
    }
}
