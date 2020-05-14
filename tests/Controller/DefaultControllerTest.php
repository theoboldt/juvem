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

use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public static function setUpBeforeClass(): void
    {
        $kernel = static::bootKernel();
        /** @var EntityManager $doctrine */
        $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Connection $connection */
        $connection = $em->getConnection();
        try {
            $connection->exec('SELECT 1 FROM flash');
        } catch (\Exception $e) {
            system('php ' . __DIR__ . '/../../app/console doctrine:database:create -q -n');
        }
        system('php ' . __DIR__ . '/../../app/console doctrine:schema:update --force -q -n');
    }

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
    
    public function testSitemapPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');
        
        $this->assertEquals(
            200, $client->getResponse()->getStatusCode()
        );
        $response = $client->getResponse();
        $this->assertEquals('application/xml', $response->headers->get('Content-Type'));
        
        $this->assertRegExp('/\w*\<loc\>[^\<\>]+\/(newsletter)/m', $response->getContent());
        $this->assertRegExp('/\w*\<loc\>[^\<\>]+\/(login)/m', $response->getContent());
        $this->assertRegExp('/\w*\<loc\>[^\<\>]+\/(register)(\/{0,1})/m', $response->getContent());
        $this->assertRegExp('/\w*\<loc\>[^\<\>]+\/(conditions-of-travel)/m', $response->getContent());
    }
    
}
