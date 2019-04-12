<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add column to store users tabindex of help in forms
 */
final class Version20190412140000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user ADD is_exclude_help_tabindex TINYINT(1) DEFAULT '0' NOT NULL");
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user DROP is_exclude_help_tabindex");
        
    }
}
