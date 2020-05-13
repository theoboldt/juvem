<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testAvailabilityHomepage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(
            200, $client->getResponse()->getStatusCode()
        );
        $this->assertStringContainsString(
            'Veranstaltung', $crawler->filter('#page-body h1')->text()
        );
    }

    public function testAvailabilityLegalPage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/legal');

        $this->assertEquals(
            200, $client->getResponse()->getStatusCode()
        );
        $this->assertStringContainsString(
            'Datenschutzerklärung', $crawler->filter('#page-body h1')
                                            ->text()
        );
    }

    public function testAvailabilityimprintPage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/imprint');

        $this->assertEquals(
            200, $client->getResponse()->getStatusCode()
        );
        $this->assertStringContainsString(
            'Impressum', $crawler->filter('#page-body h1')->text()
        );
    }

}
