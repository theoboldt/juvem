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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PublicKeyProvider
{
    const FILE_PRIVATE_KEY = 'key';

    const FILE_PUBLIC_KEY = 'pub';

    /**
     * @var string
     */
    private string $keyDir;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param string $keyDir
     */
    public function __construct(string $keyDir, ?LoggerInterface $logger = null)
    {
        $this->keyDir = $keyDir;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Ensure service is usable
     *
     * @return bool
     */
    public static function isConfigured(): bool
    {
        return function_exists('\openssl_pkey_new');
    }

    /**
     * @param int    $itemId
     * @param string $key
     * @return string
     */
    public function getItemKeyPath(int $itemId, string $key): string
    {
        return $this->keyDir . '/' . $itemId . '_' . $key;
    }


    /**
     * Check if item key available
     *
     * @param int    $itemId
     * @param string $key
     * @return bool
     */
    private function isItemKeyAvailable(int $itemId, string $key): bool
    {
        return file_exists($this->getItemKeyPath($itemId, $key));
    }

    /**
     * Determine if public/private keys are available for item
     *
     * @param int $itemId Item id
     * @return bool
     */
    public function isKeyPairAvailable(int $itemId): bool
    {
        return $this->isItemKeyAvailable($itemId, self::FILE_PRIVATE_KEY)
               && $this->isItemKeyAvailable($itemId, self::FILE_PUBLIC_KEY);
    }

    /**
     * Get item private or public key
     *
     * @param int    $itemId Item id
     * @param string $key    Key type
     * @return string|null Private or public key data
     */
    private function getItemKey(int $itemId, string $key): ?string
    {
        $path = $this->getItemKeyPath($itemId, $key);
        if (file_exists($path)) {
            $content = file_get_contents($path);
            if ($content === false) {
                throw new \RuntimeException('Failed to read from ' . $path);
            } else {
                return $content;
            }
        } else {
            return null;
        }
    }

    /**
     * Get item public key
     *
     * @param int $itemId Item id
     * @return string|null Public key data
     */
    public function getPublicKey(int $itemId): ?string
    {
        return $this->getItemKey($itemId, self::FILE_PUBLIC_KEY);
    }

    /**
     * Get item private key
     *
     * @param int $itemId Item id
     * @return string|null Private key data
     */
    public function getPrivateKey(int $itemId): ?string
    {
        return $this->getItemKey($itemId, self::FILE_PRIVATE_KEY);
    }

    /**
     * Remove key pair
     *
     * @param int $itemId Item id
     * @return bool       Returns true if actually something was removed
     */
    public function removeKeyPair(int $itemId): bool
    {
        $removed = false;
        foreach ([self::FILE_PRIVATE_KEY, self::FILE_PUBLIC_KEY] as $key) {
            if ($this->isItemKeyAvailable($itemId, $key)) {
                $path = $this->getItemKeyPath($itemId, $key);
                if (!unlink($path)) {
                    throw new \RuntimeException('Failed to remove ' . $path);
                }
                $removed = true;
            }
        }
        return $removed;
    }

    /**
     * Create public key pair for item, use transmitted password as passphrase
     *
     * @param int    $itemId
     * @param string $password
     * @return void
     */
    public function createKeyPair(int $itemId, string $password): void
    {
        $timeStart = microtime(true);

        if (!self::isConfigured()) {
            throw new \InvalidArgumentException('ext-openssl missing');
        }

        $keyOptions = [
            'private_key_bits' => 4096,
            'encrypt_key'      => true,
        ];

        $privateKey   = \openssl_pkey_new($keyOptions);
        $publicKeyPem = \openssl_pkey_get_details($privateKey)['key'];

        if (!file_exists($this->keyDir)) {
            $this->logger->notice('Going to create public key dir {dir}', ['dir' => $this->keyDir]);
            $umask = umask(0);
            if (!mkdir($this->keyDir, 0777, true)) {
                throw new \RuntimeException('Failed to create key dir');
            }
            umask($umask);
        }

        $this->logger->debug('Going to write public key for item {id}', ['id' => $itemId]);
        if (!file_put_contents(
            $this->getItemKeyPath($itemId, self::FILE_PUBLIC_KEY),
            $publicKeyPem
        )) {
            throw new \RuntimeException('Failed to write public key');
        }

        $this->logger->debug('Going to write private key for item {id}', ['id' => $itemId]);
        if (!openssl_pkey_export_to_file(
            $privateKey,
            $this->getItemKeyPath($itemId, self::FILE_PRIVATE_KEY),
            $password
        )) {
            throw new \RuntimeException('Failed to export private key');
        }

        $this->logger->notice(
            'Generated and wrote keys for item {id} within {duration} ms',
            ['id' => $itemId, 'duration' => (int)round((microtime(true) - $timeStart) * 1000)]
        );
    }
}
