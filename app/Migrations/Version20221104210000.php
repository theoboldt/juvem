<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221104210000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add possibility to store comments at acquisition attribute fillouts';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE acquisition_attribute_fillout ADD comment VARCHAR(2048) DEFAULT NULL');
        $this->addSql('ALTER TABLE acquisition_attribute ADD is_comment_enabled SMALLINT UNSIGNED DEFAULT 0 NOT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE acquisition_attribute_fillout DROP comment');
        $this->addSql('ALTER TABLE acquisition_attribute DROP is_comment_enabled');
    }
}
