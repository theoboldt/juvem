<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add cache for current weather
 */
final class Version20200128110000 extends AbstractMigration
{
    public function getDescription()
    {
        return 'Provide possibility to enable/disable location and weather information';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE event ADD is_show_address TINYINT(1) NOT NULL, ADD is_show_map TINYINT(1) NOT NULL, ADD is_show_weather TINYINT(1) NOT NULL'
        );
        $this->addSql('UPDATE event SET is_show_address = 1, is_show_map = 1, is_show_weather = 1');
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP is_show_address, DROP is_show_map, DROP is_show_weather');
    }
}
