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

use AppBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class UserPublicKeyManager extends AbstractPublicKeyManager
{
    /**
     * @var string
     */
    private string $keyDir;

    /**
     * @var LoggerInterface 
     */
    private LoggerInterface $logger;

    /**
     * @param string         $keyDir
     */
    public function __construct(string $keyDir, ?LoggerInterface $logger = null)
    {
        $this->keyDir         = $keyDir;
        $this->logger         = $logger ?? new NullLogger();
    }

    /**
     * @param int    $userId
     * @param string $key
     * @return string
     */
    private function getUserKeyPath(int $userId, string $key): string
    {
        return $this->keyDir . '/' . $userId . '_' . $key;
    }

    /**
     * @param int    $userId
     * @param string $key
     * @return bool
     */
    private function isUserKeyAvailable(int $userId, string $key): bool
    {
        return file_exists($this->getUserKeyPath($userId, $key));
    }

    /**
     * Determine if public/private keys are available for user
     *
     * @param User $user
     * @return bool
     */
    public function isKeyAvailable(User $user): bool
    {
        $userId = $user->getId();
        if (!$userId) {
            return false;
        }

        return $this->isUserKeyAvailable($userId, self::FILE_PRIVATE_KEY)
               && $this->isUserKeyAvailable($userId, self::FILE_PUBLIC_KEY);
    }

    /**
     * Ensure that a key pair is stored for a user 
     * 
     * @param User   $user
     * @param string $password
     */
    public function ensureKeyPairAvailable(User $user, string $password): void
    {
        $userId = $user->getId();
        if (!file_exists($this->getUserKeyPath($userId, self::FILE_PRIVATE_KEY))) {
            $this->createKeyPair($user, $password);
        }
    }

    /**
     * Create public key pair for user, use transmitted password as passphrase
     *
     * @param User   $user
     * @param string $password
     * @return bool
     */
    public function createKeyPair(User $user, string $password): bool
    {
        $timeStart = microtime(true);
        $userId    = $user->getId();

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

        $this->logger->debug('Going to write public key for user {id}', ['id' => $userId]); 
        if (!file_put_contents(
            $this->getUserKeyPath($userId, self::FILE_PUBLIC_KEY),
            $publicKeyPem
        )) {
            throw new \RuntimeException('Failed to write public key');
        }

        $this->logger->debug('Going to write private key for user {id}', ['id' => $userId]); 
        if (!openssl_pkey_export_to_file(
            $privateKey,
            $this->getUserKeyPath($userId, self::FILE_PRIVATE_KEY),
            $password
        )) {
            throw new \RuntimeException('Failed to export private key');
        }

        $this->logger->notice(
            'Generated and wrote keys for user {id} within {duration} ms',
            ['id' => $userId, 'duration' => (int)round((microtime(true) - $timeStart)*1000)]
        );
        return true;
    }
}
