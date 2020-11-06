<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\UploadImageManager;


class AbstractFileException extends \RuntimeException
{
    /**
     * File path
     *
     * @var string
     */
    private $path;
    
    /**
     * AbstractFileException constructor.
     *
     * @param string $path
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $path, $message = "", $code = 0, \Throwable $previous = null)
    {
        $this->path = $path;
        parent::__construct($message, $code, $previous);
    }
    
    
}