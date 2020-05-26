<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add cache for current weather
 */
final class Version20200128010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cache for current weather items';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE weather_current (weather_id INT AUTO_INCREMENT NOT NULL, provider ENUM('openweathermap'), latitude NUMERIC(10, 2) DEFAULT NULL, longitude NUMERIC(11, 2) DEFAULT NULL, details JSON DEFAULT NULL COMMENT '(DC2Type:json_array)', created_at DATETIME NOT NULL, PRIMARY KEY(weather_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB"
        );
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE weather_current');
    }
}
