<?php

namespace AppBundle\Tests\Controller;

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
        $this->assertContains(
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
        $this->assertContains(
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
        $this->assertContains(
            'Impressum', $crawler->filter('#page-body h1')->text()
        );
    }

}
