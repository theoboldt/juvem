<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Introduce field to enable empoloyee acquisition for fields
 */
final class Version20181110230000 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Introduce field to enable empoloyee acquisition for fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acquisition_attribute ADD use_at_employee SMALLINT UNSIGNED DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acquisition_attribute DROP use_at_employee');
    }
}
