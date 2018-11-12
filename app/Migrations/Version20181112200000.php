<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181112200000 extends AbstractMigration
{
    public function getDescription()
    {
        return 'Add fields to store specfic age and date for special participants list';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE event ADD specific_age INT UNSIGNED DEFAULT NULL, ADD specific_date DATE DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP specific_age, DROP specific_date');
    }
}
