<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Introduce custom field value columns in order to be able to add data there later
 */
final class Version20221105150000 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Introduce custom field value columns in order to be able to add data there later';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participation ADD custom_field_values JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE participant ADD custom_field_values JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE employee ADD custom_field_values JSON DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participation DROP custom_field_values');
        $this->addSql('ALTER TABLE participant DROP custom_field_values');
        $this->addSql('ALTER TABLE employee DROP custom_field_values');
    }
}
