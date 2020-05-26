<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add storage for entity collection changes
 */
final class Version20200219100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add storage for entity collection changes';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE entity_change
            CHANGE changes attribute_changes JSON NOT NULL COMMENT '(DC2Type:json_array)',
            ADD collection_changes JSON NOT NULL COMMENT '(DC2Type:json_array)' AFTER attribute_changes"
        );
        $this->addSql("UPDATE entity_change SET collection_changes = '[]'");
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE entity_change
            DROP collection_changes,
            CHANGE attribute_changes changes JSON NOT NULL COMMENT '(DC2Type:json_array)'"
        );
    }
}
