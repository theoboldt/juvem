<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
}
