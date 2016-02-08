<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 28.10.15
 * Time: 08:09
 */

namespace AppBundle\Response;


use AppBundle\Image\LazyImage;
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
     * @param LazyImage $image
     * @param string    $format
     * @param Request   $request
     * @param int|null  $defaultWidth
     * @param int|null  $defaultHeight
     * @return static
     */
    public static function createFromRequest(
        LazyImage $image,
        $format,
        Request $request,
        $defaultWidth = 128,
        $defaultHeight = 128
    ) {
        $response = new Response('', Response::HTTP_OK);
        self::setCacheHeaders($image, $response);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $width  = $request->query->get('width', $defaultWidth);
        $height = $request->query->get('height', $defaultHeight);

        return new static($image, $format, $width, $height);
    }

    /**
     * @param LazyImage $image
     * @param Response  $response
     */
    private static function setCacheHeaders(LazyImage $image, Response $response)
    {
        $response->setEtag($image->computeETag())
                 ->setLastModified(\DateTime::createFromFormat('U', $image->getMTime()))
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
     * @param LazyImage $image
     * @param string    $format
     * @param int|null  $width
     * @param int|null  $height
     */
    public function __construct(LazyImage $image, $format = 'png', $width = 128, $height = 128)
    {
        if ($width !== null) {
            $width = (int)$width;
        }
        if ($height !== null) {
            $height = (int)$height;
        }

        parent::__construct(
            function () use ($image, $width, $height, $format) {
                echo $image->get($width, $height, $format);
            },
            Response::HTTP_OK,
            ['Content-Type' => self::getMimeType($format)]
        );

        self::setCacheHeaders($image, $this);
    }
}
