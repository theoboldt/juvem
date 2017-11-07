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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class AdminSystemController extends Controller
{
    /**
     * Clear cache
     *
     * @Route("/admin/cache/clear", name="admin_cache_clean")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function cacheCleanAction()
    {
        $removeDirectory = function ($dir) {
            try {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );

                foreach ($files as $fileinfo) {
                    $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                    $todo($fileinfo->getRealPath());
                }

                rmdir($dir);
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $e->getMessage()
                );
            }
        };

        $removeDirectory('../app/cache/dev');
        $this->addFlash(
            'info',
            'DEV cache cleaned'
        );

        $removeDirectory('../app/cache/prod');
        $this->addFlash(
            'info',
            'PROD cache cleaned'
        );

        return $this->redirect('/');
    }

    /**
     * Perform cache warmup
     *
     * @Route("/admin/cache/warmup", name="admin_cache_warmup")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function cacheWarmupAction()
    {
        $kernel      = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input  = new ArrayInput(
            [
                'command' => 'cache:warmup'
            ]
        );
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
     * Display database status or update database
     *
     * @Route("/admin/database/{action}", requirements={"action": "(status|update)"}, name="admin_database")
     * @Security("has_role('ROLE_ADMIN')")
     * @param string $action Either status or update
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function databaseStateAction(string $action)
    {
        $kernel      = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $parameters = [
            'command' => 'doctrine:schema:update'
        ];
        switch ($action) {
            case 'status':
                $parameters['--dump-sql'] = true;
                break;
            case 'update':
                $parameters['--force'] = true;
                break;
        }

        $input  = new ArrayInput($parameters);
        $output = new BufferedOutput();
        $application->run($input, $output);

        $content = $output->fetch();

        $this->addFlash('notice', $content);
        return $this->redirect('/');
    }
}