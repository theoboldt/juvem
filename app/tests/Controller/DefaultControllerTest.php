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

use Tests\JuvemWebTestCase;

class DefaultControllerTest extends JuvemWebTestCase
{
    public function tearDown(): void
    {
        static::ensureKernelShutdown();
    }
    
    public function testAvailabilityHomepage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(
            200, $client->getResponse()->getStatusCode()
        );
        $this->assertStringContainsString(
            'Veranstaltung', $crawler->filter('#page-body h1')->text(null, true)
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
                                            ->text(null, true)
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
            'Impressum', $crawler->filter('#page-body h1')->text(null, true)
        );
    }
    
    public function testSitemapPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');
        
        $this->assertEquals(
            200, $client->getResponse()->getStatusCode()
        );
        $response = $client->getResponse();
        $this->assertEquals('application/xml', $response->headers->get('Content-Type'));
        
        $this->assertMatchesRegularExpression('/\w*\<loc\>[^\<\>]+\/(newsletter)/m', $response->getContent());
        $this->assertMatchesRegularExpression('/\w*\<loc\>[^\<\>]+\/(login)/m', $response->getContent());
        $this->assertMatchesRegularExpression('/\w*\<loc\>[^\<\>]+\/(register)(\/{0,1})/m', $response->getContent());
        $this->assertMatchesRegularExpression('/\w*\<loc\>[^\<\>]+\/(conditions-of-travel)/m', $response->getContent());
    }
    
}
