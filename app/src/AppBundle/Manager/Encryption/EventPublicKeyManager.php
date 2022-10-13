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

use AppBundle\Entity\Event;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class EventPublicKeyManager extends AbstractPublicKeyManager
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
     * @param string $keyDir
     */
    public function __construct(string $keyDir, ?LoggerInterface $logger = null)
    {
        $this->keyDir = $keyDir;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param int    $eventId
     * @param string $key
     * @return string
     */
    private function getEventKeyPath(int $eventId, string $key): string
    {
        return $this->keyDir . '/' . $eventId . '_' . $key;
    }

    /**
     * @param int    $eventId
     * @param string $key
     * @return bool
     */
    private function isEventKeyAvailable(int $eventId, string $key): bool
    {
        return file_exists($this->getEventKeyPath($eventId, $key));
    }

    /**
     * Determine if public/private keys are available for event
     *
     * @param Event $event
     * @return bool
     */
    public function isKeyAvailable(Event $event): bool
    {
        $eventId = $event->getId();
        if (!$eventId) {
            return false;
        }

        return $this->isEventKeyAvailable($eventId, self::FILE_PRIVATE_KEY)
               && $this->isEventKeyAvailable($eventId, self::FILE_PUBLIC_KEY);
    }


    /**
     * Ensure that a key pair is stored for a event
     *
     * @param Event $event
     */
    public function ensureKeyPairAvailable(Event $event): void
    {
        $eventId = $event->getId();
        if (!file_exists($this->getEventKeyPath($eventId, self::FILE_PRIVATE_KEY))) {
            $this->createKeyPair($event);
        }
    }


    /**
     * Create public key pair for event, use transmitted password as passphrase
     *
     * @param Event $event
     * @return string Chosen Password
     */
    public function createKeyPair(Event $event): string
    {
        $timeStart = microtime(true);
        $eventId   = $event->getId();
        if (!self::isConfigured()) {
            throw new \InvalidArgumentException('ext-openssl missing');
        }
        $password = bin2hex(openssl_random_pseudo_bytes(1024, $strongResult));

        if ($strongResult === false) {
            throw new \InvalidArgumentException('Failed to generate strong openssl_random_pseudo_bytes() result');
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

        $this->logger->debug('Going to write public key for event {id}', ['id' => $eventId]);
        if (!file_put_contents(
            $this->getEventKeyPath($eventId, self::FILE_PUBLIC_KEY),
            $publicKeyPem
        )) {
            throw new \RuntimeException('Failed to write public key');
        }

        $this->logger->debug('Going to write private key for event {id}', ['id' => $eventId]);
        if (!openssl_pkey_export_to_file(
            $privateKey,
            $this->getEventKeyPath($eventId, self::FILE_PRIVATE_KEY),
            $password
        )) {
            throw new \RuntimeException('Failed to export private key');
        }

        $this->logger->notice(
            'Generated and wrote keys for event {id} within {duration} ms',
            ['id' => $eventId, 'duration' => (int)round((microtime(true) - $timeStart) * 1000)]
        );
        return $password;
    }
}
