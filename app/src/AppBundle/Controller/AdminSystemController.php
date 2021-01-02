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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminSystemController extends AbstractController
{
    /**
     * Clear cache
     *
     * @Route("/admin/cache/clear", name="admin_cache_clean", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     * @param Request $request
     * @return RedirectResponse
     */
    public function cacheCleanAction(Request $request)
    {
        $rootDirPath   = $this->getParameter('app.cache.root.path');
        $webPath       = $this->getParameter('app.web.root.path');
        $hostAndScheme = $request->getSchemeAndHttpHost();
 
        $script = '<?php
$rootDirPath = "__ROOT_DIR__";
$webPath = "__WEB_PATH__";
$hostAndScheme = "__HOST_AND_SCHEME_";

$removeDirectory = function ($dir) use ($webPath) {
    $parent = dirname($dir);
    $target = $parent."/".basename($dir).time();
    if (!file_exists($parent) || !file_exists($dir)) {
        return;
    }
    if (rename($dir, $target)) {
        $dir = $target;
    }
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? "rmdir" : "unlink");
        $todo($fileinfo->getRealPath());
    }

    rmdir($dir);
};
$removeDirectory($rootDirPath . "/dev");
$removeDirectory($rootDirPath . "/prod");
unlink($webPath."/clear_cache.php");
header("Location: $hostAndScheme");
';
        $script = strtr(
            $script,
            [
                '__ROOT_DIR__'       => $rootDirPath,
                '__WEB_PATH__'       => $webPath,
                '__HOST_AND_SCHEME_' => $hostAndScheme
            ],
        );
        if (!file_put_contents($webPath.'/clear_cache.php', $script)) {
            throw new \RuntimeException('Failed to place '.$webPath.'/clear_cache.php');
        }
        
        return new RedirectResponse('/clear_cache.php');
    }

    /**
     * Display database status or update database
     *
     * @Route("/admin/database/{action}", requirements={"action": "(status|update)"}, name="admin_database")
     * @Security("is_granted('ROLE_ADMIN')")
     * @param string          $action Either status or update
     * @param KernelInterface $kernel
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function databaseStateAction(string $action, KernelInterface $kernel)
    {
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
        return new RedirectResponse('/');
    }

    /**
     * Execute migrations
     *
     * @Route("/admin/database/migrate", name="admin_database_migrate")
     * @Security("is_granted('ROLE_ADMIN')")
     * @param KernelInterface $kernel
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function databaseMigrateAction(KernelInterface $kernel)
    {
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
        return new RedirectResponse('/');
    }
}
