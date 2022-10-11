<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager\Encryption;

abstract class AbstractPublicKeyManager
{
    const FILE_PRIVATE_KEY = 'key';

    const FILE_PUBLIC_KEY = 'pub';

    /**
     * Ensure service is usable
     *
     * @return bool
     */
    public static function isConfigured(): bool
    {
        return function_exists('\openssl_pkey_new');
    }
    
}
