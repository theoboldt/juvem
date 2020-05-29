<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Increase amount of supported osm ids
 */
final class Version20200528100000 extends AbstractMigration
{
    
    public function getDescription(): string
    {
        return 'Increase amount of supported osm ids';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location_description CHANGE osm_id osm_id BIGINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location_description CHANGE osm_id osm_id INT DEFAULT NULL');
    }
}
