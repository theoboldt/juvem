<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests\Export;


use AppBundle\Entity\Event;
use AppBundle\Entity\User;
use AppBundle\Twig\GlobalCustomizationConfiguration;
use AppBundle\Twig\GlobalCustomizationConfigurationProvider;
use PHPUnit\Framework\TestCase;

abstract class ExportTestCase extends TestCase
{
    /**
     * @var array|string[]
     */
    protected static array $files = [];
    
    public static function setUpBeforeClass(): void
    {

        $tmpDir = __DIR__ . '/../../../var/tmp';
        if (!file_exists($tmpDir)) {
            $umask = umask();
            umask(0);
            if (!mkdir($tmpDir, 0777, true)) {
                umask($umask);
                throw new \RuntimeException('Precondition failed: Tmp dir inaccessible');
            }
            umask($umask);
        }
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        self::$files = [];
        parent::tearDownAfterClass();
    }
    
    /**
     * @return GlobalCustomizationConfigurationProvider
     */
    protected function customization(): GlobalCustomizationConfigurationProvider
    {
        return new GlobalCustomizationConfiguration(
            'Juvem Testorganisation',
            'Teststrasse 10',
            '7000',
            'Teststadt',
            '',
            '',
            '',
            '',
        );
    }

    /**
     * @return Event
     */
    protected function event(): Event
    {
        $event = new Event();
        $event->setTitle('Camping Trip');

        return $event;
    }

    /**
     * @return User
     */
    protected function user(): User
    {
        $user = new User();
        $user->setNameFirst('Max');
        $user->setNameLast('Muster');
        return $user;
    }
}
