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


trait TmpDirAccessingTestTrait
{

    /**
     * @var array|string[]
     */
    protected static array $files = [];

    protected static function ensureTmpDirAccessible(): void
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
    }

    protected static function removeTmpFiles(): void
    {
        foreach (self::$files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        self::$files = [];
    }

}
