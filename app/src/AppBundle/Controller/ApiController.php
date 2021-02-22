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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController
{
    
    /**
     * @Route("/api/cloud", name="api_cloud")
     * @Security("is_granted('cloud_access')")
     */
    public function statusCloudAction()
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
