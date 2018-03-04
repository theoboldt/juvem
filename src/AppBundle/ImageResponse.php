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

use AppBundle\UploadImage\UploadImage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class ImageResponse
 *
 * @package AppBundle\Response
 */
class ImageResponse extends BinaryFileResponse
{
    /**
     * Check @see Request and determine if not modified or image response should be sent
     *
     * @param UploadImage $image   Image to provide
     * @param Request     $request Request
     * @return Response|ImageResponse
     */
    public static function createFromRequest(
        UploadImage $image,
        Request $request
    )
    {
        $response = new Response('', Response::HTTP_NOT_MODIFIED);
        self::setCacheHeaders($image, $response);

        if ($response->isNotModified($request)) {
            return $response;
        }

        return new self($image);
    }

    /**
     * Set http headers and content type by image
     *
     * @param UploadImage $image    Image to provide
     * @param Response    $response Response to modify
     * @return void
     */
    private static function setCacheHeaders(UploadImage $image, Response $response)
    {
        $response->setEtag($image->getETag())
                 ->setLastModified($image->getMTime())
                 ->setMaxAge(14 * 24 * 60 * 60)
                 ->setPublic();
        $response->headers->set('Content-Type', $image->getType(true));
    }

    /**
     * Create image response
     *
     * @param UploadImage $image Image to provide
     */
    public function __construct(UploadImage $image)
    {
        parent::__construct($image->getPath(), Response::HTTP_OK, [], true, null, false, false);
        self::setCacheHeaders($image, $this);
    }
}
