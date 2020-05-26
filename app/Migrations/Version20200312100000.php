<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Added possibility to configure special link for events
 */
final class Version20200312100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added possibility to configure special link for events';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE event ADD link_title VARCHAR(32) DEFAULT NULL, ADD link_url VARCHAR(255) DEFAULT NULL"
        );
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP link_title, DROP link_url');
    }
}
