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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
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
     * @Route("/admin/cache/clear", name="admin_cache_clean", methods={"GET"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function cacheCleanAction()
    {
        $error           = false;
        $removeDirectory = function ($dir) use ($error) {
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
                $error = true;
                $this->addFlash(
                    'error',
                    $e->getMessage()
                );
            }
        };

        $removeDirectory('../app/cache/dev');
        $removeDirectory('../app/cache/prod');
        
        if ($error) {
            return new RedirectResponse('/admin/cache/clear/result');
        }
        
        return $this->redirectToRoute('admin_cache_clean_result');
    }
    /**
     * Clear cache
     *
     * @Route("/admin/cache/clear/result", name="admin_cache_clean_result", methods={"GET"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function cacheCleanedAction()
    {
        $this->addFlash(
            'info',
            'Prod/Dev cache cleaned'
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
            'info',
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

        $this->addFlash('info', 'Database: <pre>' . nl2br($content) . '</pre>');
        return $this->redirect('/');
    }

    /**
     * Execute migrations
     *
     * @Route("/admin/database/migrate", name="admin_database_migrate")
     * @Security("has_role('ROLE_ADMIN')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function databaseMigrateAction()
    {
        $kernel      = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $parameters = [
            'command'          => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
        ];

        $input  = new ArrayInput($parameters);
        $output = new BufferedOutput();
        $application->run($input, $output);

        $content = $output->fetch();

        $this->addFlash('info', 'Migrations: <pre>' . nl2br($content).'</pre>');
        return $this->redirect('/');
    }
}