<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211129180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable event feedback questionnaire and fillout';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE feedback_questionnaire_fillout (uuid VARCHAR(128) NOT NULL, eid INT NOT NULL, feedback_questionnaire JSON DEFAULT NULL COMMENT '(DC2Type:json_array)', additions LONGTEXT NOT NULL, modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_56B707554FBDA576 (eid), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB"
        );
        $this->addSql(
            "ALTER TABLE event ADD feedback_questionnaire JSON DEFAULT NULL COMMENT '(DC2Type:json_array)', ADD is_feedback_questionnaire_sent TINYINT(1) DEFAULT '0' NOT NULL, ADD feedback_day_after_event_count SMALLINT UNSIGNED DEFAULT 0 NOT NULL"
        );
        $this->addSql(
            "ALTER TABLE feedback_questionnaire_fillout ADD CONSTRAINT FK_56B707554FBDA576 FOREIGN KEY (eid) REFERENCES event (eid) ON DELETE CASCADE"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE event DROP feedback_questionnaire, DROP is_feedback_questionnaire_sent, DROP feedback_day_after_event_count'
        );
        $this->addSql('DROP TABLE feedback_questionnaire_fillout');
    }
}
