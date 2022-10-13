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

class UserPublicKeyManager
{
    /**
     * @var PublicKeyProvider
     */
    private PublicKeyProvider $publicKeyProvider;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param string               $keyDir
     * @param LoggerInterface|null $logger
     */
    public function __construct(string $keyDir, ?LoggerInterface $logger = null)
    {
        $this->publicKeyProvider = new PublicKeyProvider($keyDir, $logger);
        $this->logger            = $logger ?? new NullLogger();
    }

    /**
     * Get public key for transmitted user (id) if configured
     *
     * @param int $userId User id
     * @return string|null Public key
     */
    public function getUserPublicKey(int $userId): ?string
    {
        return $this->publicKeyProvider->getPublicKey($userId);
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

        return $this->publicKeyProvider->isKeyPairAvailable($userId);
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
        if (!$userId) {
            return;
        }
        if (!$this->publicKeyProvider->isKeyPairAvailable($userId)) {
            $this->publicKeyProvider->createKeyPair($userId, $password);
        }
    }

    /**
     * Create public key pair for user, use transmitted password as passphrase
     *
     * @param User   $user
     * @param string $password
     * @return void
     */
    public function createKeyPair(User $user, string $password): void
    {
        $userId = $user->getId();
        if (!$userId) {
            return;
        }
        $this->publicKeyProvider->createKeyPair($userId, $password);
    }
}
