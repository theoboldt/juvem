<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add cache for location descriptor elements
 */
final class Version20200122010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cache for location descriptor elements';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE location_description (element_id INT AUTO_INCREMENT NOT NULL, osm_id INT DEFAULT NULL, osm_type ENUM('N', 'W', 'R'), address_street_name VARCHAR(128) DEFAULT NULL, address_street_number VARCHAR(16) DEFAULT NULL, address_city VARCHAR(128) DEFAULT NULL, address_zip VARCHAR(16) DEFAULT NULL, address_country VARCHAR(128) DEFAULT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, details JSON DEFAULT NULL COMMENT '(DC2Type:json_array)', PRIMARY KEY(element_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB"
        );
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE location_description');
    }
}
