<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\JuvemKernelTestCase;


class DefaultCommandTest extends JuvemKernelTestCase
{
    public function testUserCleanup()
    {
        $kernel      = static::createKernel();
        $application = new Application($kernel);
        
        $command       = $application->find('app:user:cleanup');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                '--dry-run' => true,
            ]
        );
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No changes applied', $output);
    }
}