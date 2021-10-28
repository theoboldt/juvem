<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Added possibility to enable employee registration for events
 */
final class Version20211028000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added possibility to enable employee registration for events';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD is_active_registration_employee TINYINT(1) NOT NULL');
        $this->addSql('UPDATE event SET is_active_registration_employee = 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP is_active_registration_employee');
    }
}
