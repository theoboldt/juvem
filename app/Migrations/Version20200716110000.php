<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Enable participant connectors and connections
 */
final class Version20200716110000 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Enable participant connectors and connections';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE participant_connector_connection (id INT AUTO_INCREMENT NOT NULL, connector_id INT DEFAULT NULL, created_by INT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_2D33FF654D085745 (connector_id), INDEX IDX_2D33FF65DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            '     CREATE TABLE participant_connector (id INT AUTO_INCREMENT NOT NULL, aid INT DEFAULT NULL, modified_by INT DEFAULT NULL, created_by INT DEFAULT NULL, token VARCHAR(32) NOT NULL, description VARCHAR(255) NOT NULL, modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_D78E8A4A48B40DAA (aid), INDEX IDX_D78E8A4A25F94802 (modified_by), INDEX IDX_D78E8A4ADE12AB56 (created_by), INDEX deleted_at_idx (deleted_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE participant_connector_connection ADD CONSTRAINT FK_2D33FF654D085745 FOREIGN KEY (connector_id) REFERENCES participant_connector (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE participant_connector_connection ADD CONSTRAINT FK_2D33FF65DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE participant_connector ADD CONSTRAINT FK_D78E8A4A48B40DAA FOREIGN KEY (aid) REFERENCES participant (aid) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE participant_connector ADD CONSTRAINT FK_D78E8A4A25F94802 FOREIGN KEY (modified_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE participant_connector ADD CONSTRAINT FK_D78E8A4ADE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE participant_connector_connection');
        $this->addSql('DROP TABLE participant_connector');
    }
}
