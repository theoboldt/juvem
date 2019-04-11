<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add table for export templates
 */
final class Version20190411100000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE export_template (id INT AUTO_INCREMENT NOT NULL, eid INT DEFAULT NULL, modified_by INT DEFAULT NULL, created_by INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, configuration JSON DEFAULT NULL COMMENT '(DC2Type:json_array)', modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_992B5A9C4FBDA576 (eid), INDEX IDX_992B5A9C25F94802 (modified_by), INDEX IDX_992B5A9CDE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB"
        );
        $this->addSql(
            "ALTER TABLE export_template ADD CONSTRAINT FK_992B5A9C4FBDA576 FOREIGN KEY (eid) REFERENCES event (eid) ON DELETE SET NULL"
        );
        $this->addSql(
            "ALTER TABLE export_template ADD CONSTRAINT FK_992B5A9C25F94802 FOREIGN KEY (modified_by) REFERENCES `user` (uid) ON DELETE SET NULL"
        );
        $this->addSql(
            "ALTER TABLE export_template ADD CONSTRAINT FK_992B5A9CDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (uid) ON DELETE SET NULL"
        );
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE export_template');
    }
}
