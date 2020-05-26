<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190207191000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->skipIf(!$schema->getTable('participant')->hasColumn('to_pay'));
        $this->addSql('ALTER TABLE participant DROP to_pay');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participant ADD to_pay INT DEFAULT NULL');
    }
}
