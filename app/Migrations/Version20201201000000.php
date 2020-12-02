<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Enable possibility to store cloud information related to events
 */
final class Version20201201000000 extends AbstractMigration
{
    
    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Enable possibility to store cloud information related to events';
    }
    
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE event ADD cloud_team_directory_id INT UNSIGNED DEFAULT NULL, ADD cloud_team_directory_name VARCHAR(128) DEFAULT NULL, ADD cloud_management_directory_id INT UNSIGNED DEFAULT NULL, ADD cloud_management_directory_name VARCHAR(128) DEFAULT NULL'
        );
        $this->addSql(
            'ALTER TABLE event_user_assignment ADD allowed_cloud_access_team TINYINT(1) NOT NULL, ADD allowed_cloud_access_management TINYINT(1) NOT NULL'
        );
        $this->addSql('ALTER TABLE user ADD cloud_username VARCHAR(255) DEFAULT NULL');
    }
    
    /**
     * {@inheritDoc}
     */
    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE event DROP cloud_team_directory_id, DROP cloud_team_directory_name, DROP cloud_management_directory_id, DROP cloud_management_directory_name'
        );
        $this->addSql(
            'ALTER TABLE event_user_assignment DROP allowed_cloud_access_team, DROP allowed_cloud_access_management'
        );
        $this->addSql('ALTER TABLE user DROP cloud_username');
    }
}
