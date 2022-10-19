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

class EventUserPublicKeyManager
{
    /**
     * @var UserPublicKeyManager
     */
    private UserPublicKeyManager $userPublicKeyManager;

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
    public function __construct(
        string               $keyDir,
        UserPublicKeyManager $userPublicKeyManager,
        ?LoggerInterface     $logger = null
    ) {
        $this->keyDir               = $keyDir;
        $this->userPublicKeyManager = $userPublicKeyManager;
        $this->logger               = $logger ?? new NullLogger();
    }

    /**
     * Determine if encrypted password for event and user exists
     * 
     * @param int $eventId
     * @param int $userId
     * @return bool
     */
    public function isEncryptedPasswordAvailable(int $eventId, int $userId): bool
    {
        return file_exists($this->getEventUserKeyPath($eventId, $userId));
    }

    /**
     * Encrypt provided event password by user public key and store it
     *
     * @param int    $userId        User id
     * @param int    $eventId       Event id
     * @param string $eventPassword Password for decrypting event data
     * @return bool                 Returns true if password was publicly encrypted and stored
     */
    public function createEventPasswordUserEncrypted(int $userId, int $eventId, string $eventPassword): bool
    {
        $timeStart = microtime(true);
        $publicKey = $this->userPublicKeyManager->getUserPublicKey($userId);

        if (!$publicKey) {
            return false;
        }

        if (!openssl_public_encrypt($eventPassword, $encryptedPassword, $publicKey)) {
            throw new \RuntimeException(
                'Failed to encrypt using public key for event ' . $eventId . ' and user ' . $userId
            );
        }

        if (!file_exists($this->keyDir)) {
            $this->logger->notice('Going to create user-event key dir {dir}', ['dir' => $this->keyDir]);
            $umask = umask(0);
            if (!mkdir($this->keyDir, 0777, true)) {
                throw new \RuntimeException('Failed to create key dir');
            }
            umask($umask);
        }

        $this->logger->debug(
            'Going to write encrypted password for event {eid} and user {uid}',
            ['uid' => $userId, 'eid' => $eventId]
        );
        if (!file_put_contents(
            $this->getEventUserKeyPath($eventId, $userId),
            $encryptedPassword
        )) {
            throw new \RuntimeException('Failed to write event password encrypted');
        }
        $this->logger->notice(
            'Stored password for event {eid} encrypted for user {uid} within {duration} ms',
            [
                'uid'      => $userId,
                'eid'      => $eventId,
                'duration' => (int)round((microtime(true) - $timeStart) * 1000),
            ]
        );

        return true;
    }

    /**
     * @param int $eventId
     * @param int $userId
     * @return string
     */
    private function getEventUserKeyPath(int $eventId, int $userId): string
    {
        return $this->keyDir . '/' . $eventId . '_' . $userId . '.enc';
    }

}
