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

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;

class CronController extends Controller
{
    /**
     * @Route("/cron/subscription/{token}", requirements={"token": "[[:alnum:]]{1,128}"}, name="cron_subscription")
     */
    public function subscriptionMailAction($token)
    {
        if ($token != $this->getParameter('cron_secret')) {
            throw new AccessDeniedException('Called cron task with incorrect credentials');
        }

        $kernel      = $this->get('kernel');
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
     */
    public function cleanupUserRegistrationRequestsAction($token)
    {
        if ($token != $this->getParameter('cron_secret')) {
            throw new AccessDeniedException('Called cron task with incorrect credentials');
        }

        $kernel      = $this->get('kernel');
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
}
