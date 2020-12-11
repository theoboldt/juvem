<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Now storing fileshares in separate table
 */
final class Version20201211180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Now storing file shares in separate table';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE event DROP cloud_team_directory_id, DROP cloud_team_directory_name, DROP cloud_management_directory_id, DROP cloud_management_directory_name, ADD share_directory_root_href VARCHAR(128) DEFAULT NULL"
        );
        $this->addSql(
            "CREATE TABLE event_file_share (id INT AUTO_INCREMENT NOT NULL, eid INT DEFAULT NULL, purpose ENUM('team', 'management', 'gallery'), directory_id INT UNSIGNED NOT NULL, directory_href VARCHAR(255) NOT NULL, directory_name VARCHAR(128) NOT NULL, group_name VARCHAR(128) NOT NULL, INDEX IDX_1D6BF4424FBDA576 (eid), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB"
        );
        $this->addSql(
            "ALTER TABLE event_file_share ADD CONSTRAINT FK_1D6BF4424FBDA576 FOREIGN KEY (eid) REFERENCES event (eid) ON DELETE CASCADE"
        );
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE event ADD cloud_team_directory_id INT UNSIGNED DEFAULT NULL, ADD cloud_team_directory_name VARCHAR(128) DEFAULT NULL, ADD cloud_management_directory_id INT UNSIGNED DEFAULT NULL, ADD cloud_management_directory_name VARCHAR(128) DEFAULT NULL'
        );
        $this->addSql('DROP TABLE event_file_share');
    }
}
