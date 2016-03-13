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
            200, $client->getResponse()
                        ->getStatusCode()
        );
        $this->assertContains(
            'Veranstaltung', $crawler->filter('#page-body h1')
                                     ->text()
        );
        $this->assertContains(
            'Evangelisches Jugendwerk Stuttgart Vaihingen', $crawler->filter('#page-body h1 small')
                                                                    ->text()
        );
    }

    public function testAvailabilityLegalPage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/legal');

        $this->assertEquals(
            200, $client->getResponse()
                        ->getStatusCode()
        );
        $this->assertContains(
            'Datenschutzerklärung', $crawler->filter('#page-body h1')
                                            ->text()
        );
    }

    public function testAvailabilityImpressumPage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/impressum');

        $this->assertEquals(
            200, $client->getResponse()
                        ->getStatusCode()
        );
        $this->assertContains(
            'Impressum', $crawler->filter('#page-body h1')
                                 ->text()
        );
    }

}
