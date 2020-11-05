<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle;


class JsonResponse extends \Symfony\Component\HttpFoundation\JsonResponse
{

    /**
     * Factory method for json error responses
     *
     * @param  string $message Message which will be displayed in UI
     * @param  string $type    Type of error (default) or warning
     * @return self
     */
    public static function createError(string $message, string $type = 'error')
    {
        return JsonResponse::create(
            [
                'message' => [
                    'content' => $message,
                    'type'    => $type,
                ],
                'success' => false,
            ],
            self::HTTP_BAD_REQUEST
        );
    }

    /**
     * Factory method for json error responses
     *
     * @param  string $message Message which will be displayed in UI
     * @return self
     */
    public static function createSuccess(string $message)
    {
        return JsonResponse::create(
            [
                'message' => [
                    'content' => $message,
                    'type'    => 'success',
                ],
                'success' => true,
            ],
            self::HTTP_OK
        );
    }
}