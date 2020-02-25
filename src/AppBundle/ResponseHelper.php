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


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ResponseHelper
{
    
    /**
     * Set content type for response
     *
     * @param Response $response Response to modify headers
     * @param string $type       Content type to use
     */
    public static function configureContentType(Response $response, string $type): void
    {
        $response->headers->set('Content-Type', $type);
    }
    
    /**
     * Configure file disposition for request
     *
     * @param Response $response      Response to modify headers
     * @param string $filename        File name to use (will also be sanitized)
     * @param string $dispositionType Disposition type
     */
    public static function configureDisposition(
        Response $response, string $filename, string $dispositionType = ResponseHeaderBag::DISPOSITION_ATTACHMENT
    ): void
    {
        $disposition = $response->headers->makeDisposition(
            $dispositionType,
            $filename,
            preg_replace(
                '/[^a-zA-Z0-9\-\._ ]/', '', $filename
            )
        );
        $response->headers->set('Content-Disposition', $disposition);
    }
    
    /**
     * Configure file attachment including type
     *
     * @param Response $response  Response to modify headers
     * @param string $filename    File name to use (will also be sanitized)
     * @param string $contentType Content type to configure
     */
    public static function configureAttachment(Response $response, string $filename, string $contentType): void
    {
        self::configureDisposition($response, $filename);
        self::configureContentType($response, $contentType);
    }
    
}