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

use PHPUnit\Runner\AfterLastTestHook;

class MemoryLimitConfiguratorExtension implements AfterLastTestHook
{
    /**
     * @var int
     */
    private $memoryLimitGb;

    /**
     * @param int $memoryLimitGb
     */
    public function __construct(int $memoryLimitGb = 1)
    {
        $this->memoryLimitGb = $memoryLimitGb;
    }

    /**
     * @return void
     */
    public function executeAfterLastTest(): void
    {
        if ($this->memoryLimitGb <= 0) {
            return;
        }
        ini_set('memory_limit', (int)$this->memoryLimitGb.'G');
    }
}
