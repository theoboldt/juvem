<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AdminSystemController extends Controller
{
    /**
     * Page for list of events
     *
     * @Route("/admin/cache/clear", name="admin_cache_clean")
     */
    public function cacheCleanAction()
    {
        $kernel = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
           'command' => 'cache:clear'
        ));
        $output = new BufferedOutput();
        $application->run($input, $output);

        $content = $output->fetch();

        $this->addFlash(
            'notice',
            $content
        );
        return $this->redirect('/');
    }

    /**
     * Page for list of events
     *
     * @Route("/admin/cache/warmup", name="admin_cache_warmup")
     */
    public function cacheWarmupAction()
    {
        $kernel = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
           'command' => 'cache:warmup'
        ));
        $output = new BufferedOutput();
        $application->run($input, $output);

        $content = $output->fetch();

        $this->addFlash(
            'notice',
            $content
        );
        return $this->redirect('/');
    }

}