<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add possibility to store user specific attachments
 */
final class Version20230403110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add possibility to store user specific attachments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE user_attachment (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, filename VARCHAR(255) DEFAULT NULL, filename_original VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_DE381F57A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE user_attachment ADD CONSTRAINT FK_DE381F57A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (uid)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_attachment');
    }
}
