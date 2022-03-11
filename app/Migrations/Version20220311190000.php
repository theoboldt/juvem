<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220311190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added configuration option for event calendar integration switch';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD is_calendar_entry_enabled TINYINT(1) NOT NULL');
        $this->addSql('UPDATE event SET is_calendar_entry_enabled=1');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP is_calendar_entry_enabled');
    }
}
