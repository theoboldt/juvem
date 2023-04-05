<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add possibility to configure user attachments on newsletters
 */
final class Version20230404000000 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Add possibility to configure user attachments on newsletters';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE newsletter_user_attachment (lid INT NOT NULL, user_attachment_id INT NOT NULL, INDEX IDX_98B1C371406C9EF9 (lid), INDEX IDX_98B1C371EAEA4BDB (user_attachment_id), PRIMARY KEY(lid, user_attachment_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE newsletter_user_attachment ADD CONSTRAINT FK_98B1C371406C9EF9 FOREIGN KEY (lid) REFERENCES newsletter (lid) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE newsletter_user_attachment ADD CONSTRAINT FK_98B1C371EAEA4BDB FOREIGN KEY (user_attachment_id) REFERENCES user_attachment (id) ON DELETE CASCADE'
        );
    }
    
    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE newsletter_user_attachment');
    }
}
