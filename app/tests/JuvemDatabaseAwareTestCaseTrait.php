<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests;


use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\KernelInterface;

trait JuvemDatabaseAwareTestCaseTrait
{
    
    /**
     * Boots the Kernel for this test.
     *
     * @return KernelInterface A KernelInterface instance
     */
    abstract protected static function bootKernel(array $options = []);
    
    /**
     * Shuts the kernel down if it was used in the test - called by the tearDown method by default.
     */
    abstract protected static function ensureKernelShutdown();
    
    /**
     * Ensure database schema is created and up-to-date
     */
    public static function ensureDatabaseValid(): void
    {
        $consoleFile =  __DIR__ . '/../console';
        if (!file_exists($consoleFile) || !is_readable($consoleFile)) {
            throw new \RuntimeException('Console file '.$consoleFile.' not existing/readable');
        }
        
        $kernel = static::bootKernel();
        /** @var EntityManager $doctrine */
        $em = $kernel->getContainer()->get('doctrine');
        /** @var Connection $connection */
        $connection = $em->getConnection();
        try {
            $connection->exec('SELECT 1 FROM flash');
        } catch (\Exception $e) {
            system('php ' . $consoleFile . ' doctrine:database:create -q -n');
        } finally {
            static::ensureKernelShutdown();
        }
        system('php ' . $consoleFile . ' doctrine:schema:update --force -q -n');
    }
}