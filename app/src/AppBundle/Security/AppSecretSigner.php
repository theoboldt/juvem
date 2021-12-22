<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Security;

class AppSecretSigner
{

    const ALGO = 'sha256';

    private string $secret;

    /**
     * @param string $secret
     */
    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Create signature of string
     *
     * @param string $data
     * @return string
     * @throws HashAlgorithmUnavailableException
     */
    public function signString(string $data)
    {
        $signature = hash_hmac(self::ALGO, $data, $this->secret);
        if ($signature === false) {
            throw new HashAlgorithmUnavailableException(self::ALGO . ' unavailable');
        }
        return $signature;
    }

    /**
     * Create signature of array data
     *
     * @param array $data
     * @return string
     */
    public function signArray(array $data)
    {
        return $this->signString(json_encode($data));
    }

    /**
     * Verify signed string
     *
     * @param string $data
     * @param string $givenSignature
     * @return bool
     */
    public function isStringValid(string $data, string $givenSignature): bool
    {
        $expectedSignature = $this->signString($data);
        return (hash_equals($expectedSignature, $givenSignature));
    }

    /**
     * Verify signed array
     *
     * @param array  $data
     * @param string $givenSignature
     * @return bool
     */
    public function isArrayValid(array $data, string $givenSignature): bool
    {
        $expectedSignature = $this->signArray($data);
        return (hash_equals($expectedSignature, $givenSignature));
    }

}
