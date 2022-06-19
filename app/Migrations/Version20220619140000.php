<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Attendance lists, columns and choices now use soft delete
 */
final class Version20220619140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Attendance lists, columns and choices now use soft delete';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attendance_list ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE attendance_list_column_choices ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE attendance_list_column ADD deleted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attendance_list DROP deleted_at');
        $this->addSql('ALTER TABLE attendance_list_column_choices DROP deleted_at');
        $this->addSql('ALTER TABLE attendance_list_column DROP deleted_at');
    }
}
