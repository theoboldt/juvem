<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 28.10.15
 * Time: 08:09
 */

namespace AppBundle;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class ImageResponse
 *
 * @package AppBundle\Response
 */
class ImageResponse extends StreamedResponse
{
    /**
     * @param UploadImage $image
     * @param string $format
     * @param Request $request
     * @param int|null $defaultWidth
     * @param int|null $defaultHeight
     * @return static
     */
    public static function createFromRequest(
        UploadImage $image,
        $format,
        Request $request,
        $defaultWidth = 128,
        $defaultHeight = 128
    )
    {
        $response = new Response('', Response::HTTP_OK);
        self::setCacheHeaders($image, $response);

        if ($response->isNotModified($request)) {
            return $response;
        }

//        $image  = $request->query->get('image');
        $width  = $request->query->get('width', $defaultWidth);
        $height = $request->query->get('height', $defaultHeight);

        return new static($image, $format, $width, $height);
    }

    /**
     * @param UploadImage $image
     * @param Response $response
     */
    private static function setCacheHeaders(UploadImage $image, Response $response)
    {
        $response->setEtag($image->getETag())
                 ->setLastModified($image->getMTime())
                 ->setMaxAge(24 * 60 * 60)
                 ->setPublic();
    }

    /**
     * @param string $format
     * @return string
     */
    private static function getMimeType($format)
    {
        return ($format == 'png') ? 'image/png' : 'image/jpeg';
    }

    /**
     * Constructor
     *
     * @param UploadImage $image
     */
    public function __construct(UploadImage $image)
    {
        parent::__construct(
            function () use ($image) {
                echo $image->get();
            },
            Response::HTTP_OK,
            ['Content-Type' => $image->getType(true)]
        );

        self::setCacheHeaders($image, $this);
    }
}
