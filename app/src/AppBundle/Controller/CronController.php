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

use AppBundle\Command\CalculateRelatedParticipantsCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class CronController extends AbstractController
{
    /**
     * @Route("/cron/subscription/{token}", requirements={"token": "[[:alnum:]]{1,128}"}, name="cron_subscription")
     * @param string $token
     * @param KernelInterface $kernel
     * @return Response
     */
    public function subscriptionMailAction(string $token, KernelInterface $kernel)
    {
        if ($token != $this->getParameter('cron_secret')) {
            throw new AccessDeniedException('Called cron task with incorrect credentials');
        }

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input  = new ArrayInput(['command' => 'app:event:subscription']);
        $output = new BufferedOutput();
        $result = $application->run($input, $output);

        if ($result) {
            return new Response($output->fetch(), Response::HTTP_NOT_FOUND);
        } else {
            return new Response('Successfully sent');
        }
    }
    
    /**
     * @Route("/cron/user/{token}", requirements={"token": "[[:alnum:]]{1,128}"}, name="cron_clenaup_user")
     * @param string $token
     * @param KernelInterface $kernel
     * @return Response
     */
    public function cleanupUserRegistrationRequestsAction(string $token, KernelInterface $kernel)
    {
        if ($token != $this->getParameter('cron_secret')) {
            throw new AccessDeniedException('Called cron task with incorrect credentials');
        }

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input  = new ArrayInput(['command' => 'app:user:cleanup']);
        $output = new BufferedOutput();
        $result = $application->run($input, $output);

        if ($result) {
            return new Response($output->fetch(), Response::HTTP_NOT_FOUND);
        } else {
            return new Response('Successfully removed');
        }
    }


    /**
     * @Route("/cron/participants-related/{token}", requirements={"token": "[[:alnum:]]{1,128}"}, name="cron_participants_related")
     * @param string $token
     * @param KernelInterface $kernel
     * @return Response
     */
    public function relatedParticipantsFinderAction(string $token, KernelInterface $kernel)
    {
        if ($token != $this->getParameter('cron_secret')) {
            throw new AccessDeniedException('Called cron task with incorrect credentials');
        }

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input  = new ArrayInput(['command' => CalculateRelatedParticipantsCommand::NAME]);
        $output = new BufferedOutput();
        $result = $application->run($input, $output);

        if ($result) {
            return new Response($output->fetch(), Response::HTTP_NOT_FOUND);
        } else {
            return new Response('Successfully calculated');
        }
    }
}
