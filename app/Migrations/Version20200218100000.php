<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add storage for change tracking
 */
final class Version20200218100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add storage for change tracking';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE entity_change (cid INT AUTO_INCREMENT NOT NULL, uid INT DEFAULT NULL, related_id INT NOT NULL, related_final class VARCHAR(255) NOT NULL, operation ENUM('create', 'update', 'delete', 'trash', 'restore'), changes JSON NOT NULL COMMENT '(DC2Type:json_array)', occurrence_date DATETIME NOT NULL, INDEX IDX_849A40AD539B0606 (uid), INDEX index_related (related_id, related_final class), PRIMARY KEY(cid)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB"
        );
        
        $this->addSql(
            "ALTER TABLE entity_change ADD CONSTRAINT FK_849A40AD539B0606 FOREIGN KEY (uid) REFERENCES user (uid) ON DELETE SET NULL"
        );
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE entity_change');
    }
}
