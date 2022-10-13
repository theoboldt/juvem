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

class EventPublicKeyManager
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

        return $this->publicKeyProvider->isKeyPairAvailable($eventId);
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
        if (!PublicKeyProvider::isConfigured()) {
            throw new \InvalidArgumentException('ext-openssl missing');
        }
        $password = bin2hex(openssl_random_pseudo_bytes(1024, $strongResult));

        if ($strongResult === false) {
            throw new \InvalidArgumentException('Failed to generate strong openssl_random_pseudo_bytes() result');
        }
        $this->logger->notice(
            'Generated password for event {id} within {duration} ms',
            ['id' => $eventId, 'duration' => (int)round((microtime(true) - $timeStart) * 1000)]
        );

        $this->publicKeyProvider->createKeyPair($eventId, $password);

        return $password;
    }

}
