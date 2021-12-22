<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Convert Questionnaire Feedback UUID identifier into numeric auto increment
 */
final class Version20211217180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert Questionnaire Feedback UUID identifier into numeric auto increment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE feedback_questionnaire_fillout ADD id INT AUTO_INCREMENT NOT NULL, DROP uuid, DROP additions, DROP PRIMARY KEY, ADD PRIMARY KEY (id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE feedback_questionnaire_fillout ADD uuid VARCHAR(128) NOT NULL, ADD additions LONGTEXT NOT NULL'
        );
        $this->addSql('UPDATE feedback_questionnaire_fillout SET uuid=(SELECT uuid())');
        $this->addSql(
            'ALTER TABLE feedback_questionnaire_fillout DROP PRIMARY KEY, DROP id, ADD PRIMARY KEY (uuid)'
        );
    }
}
