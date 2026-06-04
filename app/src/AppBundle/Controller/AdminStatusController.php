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

use AppBundle\Http\Annotation\CloseSessionEarly;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminStatusController extends AbstractController
{
    /**
     * @CloseSessionEarly
     * @Route("/admin/status", name="admin_status_main")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function listAction()
    {
        return $this->render(
            'status/main.html.twig',
            [
                'php_version' => phpversion(),
                'ext_zip' => phpversion('zip'),
                'ext_exif' => phpversion('exif'),
                'ext_json' => phpversion('json'),
                'ext_mbstring' => phpversion('mbstring'),
                'ext_pdo' => phpversion('pdo'),
                'ext_xmlwriter' => phpversion('xmlwriter'),
                'ext_simplexml' => phpversion('simplexml'),
                'ext_xsl' => phpversion('xsl'),
                'ext_fileinfo' => phpversion('fileinfo'),
                'ext_openssl' => phpversion('openssl'),
                'ext_imap' => phpversion('imap'),
            ]
        );
    }
}