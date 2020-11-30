<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Filesharing;


use GuzzleHttp\Client;

class NextcloudOcsConnector extends AbstractNextcloudConnector
{
    const API_PATH = '/ocs/v1.php/';

    protected function client(): Client
    {
        throw new \RuntimeException('Not yet implemented');
        // TODO: Implement client() method.
    }
}
