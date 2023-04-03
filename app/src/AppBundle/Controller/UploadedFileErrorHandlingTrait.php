<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait UploadedFileErrorHandlingTrait
{

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Provide translated error message if error ocurred or null
     *
     * @param UploadedFile $file
     * @return string|null
     */
    public function provideFileErrorMessage(UploadedFile $file): ?string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $fileSizeLimit = (int)(UploadedFile::getMaxFilesize() / 1024);
            switch ($file->getError()) {
                case \UPLOAD_ERR_INI_SIZE:
                    $this->logger->warning(
                        'File {file} exceeded upload_max_filesize ini directive (limit is {limit} KiB)',
                        [
                            'file'  => $file->getClientOriginalName(),
                            'limit' => $fileSizeLimit,
                        ]
                    );
                    return 'Dateien größer als ' . $fileSizeLimit . ' KB können nicht hochgeladen werden.';
                case \UPLOAD_ERR_FORM_SIZE:
                    return 'Die im Formular zulässige Dateigröße wurde überschritten.';
                case \UPLOAD_ERR_PARTIAL:
                    $this->logger->warning(
                        'File {file} was only partially uploaded',
                        [
                            'file' => $file->getClientOriginalName(),
                        ]
                    );
                    return 'Die Datei wurde nur teilweise hochgeladen. Bitte versuchen Sie es erneut.';
                case \UPLOAD_ERR_NO_FILE:
                    return 'Die Datei wurde nicht hochgeladen. Bitte versuchen Sie es erneut.';
                case \UPLOAD_ERR_CANT_WRITE:
                    $this->logger->error(
                        'File {file} could not be written on disk',
                        [
                            'file' => $file->getClientOriginalName(),
                        ]
                    );
                    return 'Die Datei wurde nicht hochgeladen. Bitte versuchen Sie es erneut.';
                case \UPLOAD_ERR_NO_TMP_DIR:
                    $this->logger->error(
                        'File {file} could not be uploaded: missing temporary directory.',
                        [
                            'file' => $file->getClientOriginalName(),
                        ]
                    );
                    return 'Im Moment können keine Dateien hochgeladen werden. Bitte versuchen Sie es später erneut oder wenden Sie sich an den Dienstleister (T).';
                case \UPLOAD_ERR_EXTENSION:
                    $this->logger->error(
                        'File {file} upload was stopped by a PHP extension.',
                        [
                            'file' => $file->getClientOriginalName(),
                        ]
                    );
                    return 'Im Moment können keine Dateien hochgeladen werden. Bitte versuchen Sie es später erneut oder wenden Sie sich an den Dienstleister (E).';
                    break;
            }

            $this->logger->error(
                'Unknown file {file} error with message occurred: {message}',
                [
                    'file'    => $file->getClientOriginalName(),
                    'message' => $file->getErrorMessage(),
                ]
            );
            return $file->getErrorMessage();
        } else {
            return null;
        }
    }
}
